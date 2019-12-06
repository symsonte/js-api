<?php

namespace Symsonte\JsApi\StaticCacheApi;

use Symsonte\JsApi\Api;
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
     * @var TabCode
     */
    private $tabCode;

    /**
     * @param OrdinaryApi\TokenizeFunction $tokenizeFunction
     * @param Api\Func\Body\RenderFetch    $renderFetch
     * @param TabCode                      $tabCode
     */
    public function __construct(
        OrdinaryApi\TokenizeFunction $tokenizeFunction,
        Api\Func\Body\RenderFetch $renderFetch,
        TabCode $tabCode
    ) {
        $this->tokenizeFunction = $tokenizeFunction;
        $this->renderFetch = $renderFetch;
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

        $parameters = [];
        foreach ($function['cacheSet']['parameters'] as $parameter) {
            if (
                $parameter == 'user'
                && in_array(
                    'http\request\user',
                    $function['domains']
                )
            ) {
                $parameter = 'token';
            }

            $parameters[] = sprintf("\${hash(%s)}", $parameter);
        }

        $parameters = implode('-', $parameters);

        /** @var Api\Func\Body\FetchTokenization $fetch */
        $fetch = $tokenization->body->items['fetch'];

        $fetch->then = array_merge(
            [
                sprintf("
Platform.cache.set(`%s-%s`, {response: response}).catch(console.log);
",
                    $function['url'],
                    $parameters
                )
            ],
            $fetch->then
        );

        $fetch = sprintf("
Platform.cache.get(`%s-%s`)
    .then((file) => {
        if (file) {
            const {response} = file;
                
            %s
            
            return;
        }
        
        %s
    });
",
            $function['url'],
            $parameters,
            $fetch->then['resolve'],
            $this->renderFetch->render($fetch)
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