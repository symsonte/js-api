<?php

namespace Symsonte\JsApi\StaticCacheApi;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\OrdinaryApi;
use LogicException;

/**
 * @di\service()
 */
class TokenizeFunction implements Api\TokenizeFunction
{
    /**
     * @var OrdinaryApi\TokenizeFunction
     */
    private $tokenizeFunction;

    /**
     * @param OrdinaryApi\TokenizeFunction $tokenizeFunction
     */
    public function __construct(
        OrdinaryApi\TokenizeFunction $tokenizeFunction
    ) {
        $this->tokenizeFunction = $tokenizeFunction;
    }

    /**
     * {@inheritDoc}
     */
    public function tokenize(
        array $function
    ) {
        $tokenization = $this->tokenizeFunction->tokenize(
            $function
        );

        /** @var Api\Body\FetchTokenization $fetch */
        $fetch = $tokenization->body->items['fetch'];

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

        $fetch->layout = sprintf("
Platform.cache.get(`%s-%s`)
    .then((file) => {
        if (file) {
            const {response} = file;
                
            %s
            
            return;
        }
        
        %%s
    });
",
            $function['url'],
            $parameters,
            $fetch->then['resolve']
        );

        array_unshift(
            $fetch->then,
            sprintf("
Platform.cache.set(`%s-%s`, {response: response}).catch(console.log);
",
                $function['url'],
                $parameters
            )
        );

        return $tokenization;
    }
}