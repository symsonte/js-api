<?php

namespace Symsonte\JsApi\CollectCacheApi;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\CalculateExpiry;
use Symsonte\JsApi\OrdinaryApi;
use Symsonte\JsApi\TabCode;

/**
 * @di\service()
 */
class RenderFunction
{
    /**
     * @var OrdinaryApi\TokenizeFunction
     */
    private $tokenizeFunction;

    /**
     * @var Api\Func\Body\RenderFetch
     */
    private $renderFetch;

    /**
     * @var CalculateExpiry
     */
    private $calculateExpiry;

    /**
     * @var TabCode
     */
    private $tabCode;

    /**
     * @param OrdinaryApi\TokenizeFunction $tokenizeFunction
     * @param Api\Func\Body\RenderFetch    $renderFetch
     * @param CalculateExpiry              $calculateExpiry
     * @param TabCode                      $tabCode
     */
    public function __construct(
        OrdinaryApi\TokenizeFunction $tokenizeFunction,
        Api\Func\Body\RenderFetch $renderFetch,
        CalculateExpiry $calculateExpiry,
        TabCode $tabCode
    ) {
        $this->tokenizeFunction = $tokenizeFunction;
        $this->renderFetch = $renderFetch;
        $this->calculateExpiry = $calculateExpiry;
        $this->tabCode = $tabCode;
    }

    /**
     * @param array $function
     *
     * @return string
     */
    public function render(
        array $function
    ) {
        $tokenization = $this->tokenizeFunction->tokenize(
            $function
        );

        /** @var Api\Func\Body\FetchTokenization $fetch */
        $fetch = $tokenization->body->items['fetch'];

        $resolve = $fetch->then['resolve'];

        $fetch->then = [
            sprintf('resolve({table: %s, payload: response.payload})', $function['cacheSet']['parameter'])
        ];

        $fetch1 = $this->tabCode->tab(
            $this->renderFetch->render($fetch),
            4
        );

        $expiration = isset($function['cacheSet']['expiry'])
            ? $this->calculateExpiry->calculate($function['cacheSet']['expiry'])
            : INF;

        $fetch->parameters['payload'][$function['cacheSet']['parameter']] = sprintf('%s: table', $function['cacheSet']['parameter']);

        $fetch->then = [
            'resolve({table: table, payload: response.payload});'
        ];

        $fetch2 = $this->tabCode->tab(
            $this->renderFetch->render($fetch),
            4
        );

        $fetch = str_replace(
            [
                '%url%',
                '%parameter%',
                '%key%',
                '%fetch1%',
                '%fetch2%',
                '%expiration%',
                '%resolve%'
            ],
            [
                $function['url'],
                $function['cacheSet']['parameter'],
                $function['cacheSet']['keys'][0],
                $fetch1,
                $fetch2,
                $expiration,
                $resolve
            ],
            '
Platform.cache.get("%url%")
    .then((file) => {
        const now = Date.now();
        
        Promise.all([
            // Items with no cache
            new Promise((resolve) => {
                if (file) {
                    const {table} = file;
            
                    // Just get %parameter% with no cache
                    %parameter% = %parameter%.filter((%key%) => {
                        return table.indexOf(%key%) === -1
                    });
                    
                    if (%parameter%.length === 0) {
                        // No need to call api
                        resolve({table: [], payload: []});
                        
                        return;
                    }
                }
                
                %fetch1%
            }),
            // Items with cache
            new Promise((resolve) => {
                if (!file) {
                    resolve({table: [], payload: []});
                }
                
                let {date, table, response} = file;

                // Cache not expired?
                if (date + %expiration% >= now) {
                    resolve({table: table, payload: response.payload});
                    
                    return;
                }
                
                %fetch2%
            }),
        ])
            .then((result) => {
                let table = union(
                    result[0].table,
                    result[1].table
                );
                
                let payload = unionBy(
                    result[0].payload,
                    result[1].payload,
                    "%key%"
                );
                
                // Remove duplicated
                
                table = uniq(table);
                
                payload = uniqWith(
                    payload,
                    (a, b) => {
                        return a.%key% === b.%key%;
                    }
                );
                
                const response = {
                    code: "return",
                    payload: payload
                };
                
                Platform.cache.set(`%url%`, {table: table, response: response, date: now}).catch(console.log);
                
                %resolve%
            });
    });
'
        );

        $tokenization->body->items['fetch'] = $fetch;

        return sprintf("(
    %s
) => {
    %s
}",
            $this->tabCode->tab(
                implode(",\n", $tokenization->parameters),
                1
            ),
            $this->tabCode->tab(
                implode("\n", $tokenization->body->items),
                1
            )
        );
    }
}