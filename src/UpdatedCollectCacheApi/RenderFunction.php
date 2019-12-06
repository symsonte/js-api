<?php

namespace Symsonte\JsApi\UpdatedCollectCacheApi;

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

        if (isset($function['cacheSet']['updated'])) {
            unset($tokenization->parameters[$function['cacheSet']['updated']]);
        }

        /** @var Api\Func\Body\FetchTokenization $fetch */
        $fetch = $tokenization->body->items['fetch'];

        $resolve = $fetch->then['resolve'];

        $fetch->parameters['payload'][$function['cacheSet']['parameter']] = sprintf(
            '%s: %s',
            $function['cacheSet']['parameter'],
            sprintf('new%s', ucfirst($function['cacheSet']['parameter']))
        );

        if (isset($function['cacheSet']['updated'])) {
            $fetch->parameters['payload'][$function['cacheSet']['updated']] = 'updated: null';
        }

        $fetch->then = [
            str_replace(
                [
                    '%url%',
                    '%new_parameter%',
                ],
                [
                    $function['url'],
                    sprintf('new%s', ucfirst($function['cacheSet']['parameter'])),
                ],
                '
resolve(response.payload);

if (response.payload.length > 0) {
    Platform.cache.get("%url%")
        .then((cache) => {
            if (!cache) {
                cache = {
                    table: [],
                    response: {
                        payload: []
                    },
                    date: now
                };
            }
        
            Platform.cache.set(
                `%url%`, 
                {
                    ...cache,
                    table: cache.table.concat(%new_parameter%),
                    response: {
                        ...cache.response,
                        payload: cache.response.payload.concat(response.payload)
                    }
                }
            ).catch(console.log);
        });
}            
'
            )
        ];

        $fetch1 = $this->tabCode->tab(
            $this->renderFetch->render($fetch),
            4
        );

        if (isset($function['cacheSet']['expiry'])) {
            $expiration = $this->calculateExpiry->calculate($function['cacheSet']['expiry']);
        } else {
            $expiration = 9999999999;
        }

        $fetch->parameters['payload'][$function['cacheSet']['parameter']] = sprintf(
            '%s: %s',
            $function['cacheSet']['parameter'],
            sprintf('cache%s', ucfirst($function['cacheSet']['parameter']))
        );

        if (isset($function['cacheSet']['updated'])) {
            $fetch->parameters['payload'][$function['cacheSet']['updated']] = 'updated: cache.date';
        }

        $fetch->then = [
            str_replace(
                [
                    '%url%',
                    '%parameter%',
                    '%cache_parameter%',
                    '%key%',
                ],
                [
                    $function['url'],
                    $function['cacheSet']['parameter'],
                    sprintf('cache%s', ucfirst($function['cacheSet']['parameter'])),
                    $function['cacheSet']['keys'][0],
                ],
                '
if (response.payload.length > 0) {
    Platform.cache.get("%url%")
        .then((cache) => {
            cache = {
                ...cache,
                date: now
            };
            
            cache = {
                ...cache,
                response: {
                    payload: unionBy(
                        response.payload,
                        cache.response.payload,
                        "%key%"
                    )
                },
            };
            
            Platform.cache.set(
                `%url%`, 
                cache
            ).catch(console.log);
        })
}
'
            )
        ];

        $fetch2 = $this->tabCode->tab(
            $this->renderFetch->render($fetch),
            4
        );

        $fetch = str_replace(
            [
                '%url%',
                '%parameter%',
                '%new_parameter%',
                '%cache_parameter%',
                '%key%',
                '%fetch1%',
                '%fetch2%',
                '%expiration%',
                '%resolve%'
            ],
            [
                $function['url'],
                $function['cacheSet']['parameter'],
                sprintf('new%s', ucfirst($function['cacheSet']['parameter'])),
                sprintf('cache%s', ucfirst($function['cacheSet']['parameter'])),
                $function['cacheSet']['keys'][0],
                $fetch1,
                $fetch2,
                $expiration,
                $resolve
            ],
            '
Platform.cache.get("%url%")
    .then((cache) => {
        const now = Date.now();
        
        Promise.all([
            // Items with no cache
            new Promise((resolve) => {
                let %new_parameter% = %parameter%;
                
                if (cache) {
                    // Just get %parameter% with no cache
                    %new_parameter% = %new_parameter%.filter((%key%) => {
                        return cache.table.indexOf(%key%) === -1
                    });
                    
                    if (%new_parameter%.length === 0) {
                        // No need to call api
                        resolve([]);
                        
                        return;
                    }
                }
                
                %fetch1%
            }),
            // Items with cache
            new Promise((resolve) => {
                let %cache_parameter% = %parameter%;
            
                if (!cache) {
                    resolve([]);
                }
                
                // Just get %parameter% in cache
                %cache_parameter% = %cache_parameter%.filter((%key%) => {
                    return cache.table.indexOf(%key%) !== -1
                });

                // No items in cache because everything is new?
                if (%cache_parameter%.length === 0) {
                    resolve([]);
                    
                    return;
                }
                
                // Cache not expired?
                if (cache.date + %expiration% >= now) {
                    // Return just requested items, not the whole cache
                    resolve(cache.response.payload.filter(({%key%}) => {
                        return %cache_parameter%.indexOf(%key%) !== -1 
                    }));
                    
                    return;
                }
                
                resolve(cache.response.payload);

                %fetch2%
            }),
        ])
            .then((result) => {
                const response = {
                    code: "return",
                    payload: []
                        .concat(result[0])
                        .concat(result[1])
                };
                
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