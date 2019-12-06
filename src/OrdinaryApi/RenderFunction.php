<?php

namespace Symsonte\JsApi\OrdinaryApi;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\TabCode;

/**
 * @di\service()
 */
class RenderFunction
{
    /**
     * @var TokenizeFunction
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
     * @param TokenizeFunction          $tokenizeFunction
     * @param Api\Func\Body\RenderFetch $renderFetch
     * @param TabCode                   $tabCode
     */
    public function __construct(
        TokenizeFunction $tokenizeFunction,
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
        $tokenization = $this->tokenizeFunction->tokenize($function);

        /** @var Api\Func\Body\FetchTokenization $item */
        $fetch = $tokenization->body->items['fetch'];

        $fetch = $this->renderFetch->render($fetch);

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