<?php

namespace Symsonte\JsApi\Api;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\TabCode;

/**
 * @di\service()
 */
class RenderFunction
{
    /**
     * @var TabCode
     */
    private $tabCode;

    /**
     * @param TabCode $tabCode
     */
    public function __construct(
        TabCode $tabCode
    ) {
        $this->tabCode = $tabCode;
    }

    /**
     * @param Tokenization $tokenization
     *
     * @return string
     */
    public function render(
        Tokenization $tokenization
    ) {
        /** @var Api\Body\FetchTokenization $item */
        $fetch = $tokenization->body->items['fetch'];

        $parameters = sprintf("
%s,
%s,
%s,
{
    %s
}
",
            $fetch->parameters['server'],
            $fetch->parameters['session'],
            $fetch->parameters['token'],
            $this->tabCode->tab(
                implode(",\n", $fetch->parameters['payload']),
                1
            )
        );

        $then = implode("\n", $fetch->then);

        $then = sprintf("
api(%s\n)
    .then((response) => {
        %s    
    })
    .catch((response) => {
        const {code} = response;
        
        switch (code) {
            case 'connection':
                onConnectionException();
            
                break;
            case 'server':
                onServerException();
            
                break;
            default:
                onUnknownException(response);
        }
    });
",
            $this->tabCode->tab(
                $parameters,
                1
            ),
            $this->tabCode->tab(
                $then,
                2
            )
        );

        $fetch = sprintf(
            $fetch->layout,
            $then
        );

        $tokenization->body->items['fetch'] = $fetch;

        $render = sprintf("(
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

        return $render;
    }
}