<?php

namespace Symsonte\JsApi\Api\Func\Body;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\TabCode;

/**
 * @di\service()
 */
class RenderFetch
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
     * @param FetchTokenization $tokenization
     *
     * @return string
     */
    public function render(
        FetchTokenization $tokenization
    ) {
        $parameters = sprintf("
%s,
%s,
%s,
{
    %s
}
",
            $tokenization->parameters['server'],
            $tokenization->parameters['device'],
            $tokenization->parameters['token'],
            $this->tabCode->tab(
                implode(",\n", $tokenization->parameters['payload']),
                1
            )
        );

        $then = implode("\n", $tokenization->then);

        return sprintf("
api(%s\n)
    .then((response) => {
        %s    
    })
    .catch((response) => {
        const {code} = response;
        
        switch (code) {
            case \"connection\":
                onConnectionException();
            
                break;
            case \"server\":
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
    }
}