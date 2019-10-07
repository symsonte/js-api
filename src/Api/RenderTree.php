<?php

namespace Symsonte\JsApi\Api;

use Symsonte\JsApi\TabCode;

/**
 * @di\service()
 */
class RenderTree
{
    /**
     * @var TokenizeFunction
     */
    private $tokenizeFunction;

    /**
     * @var RenderFunction
     */
    private $renderFunction;

    /**
     * @var TabCode
     */
    private $tabCode;

    /**
     * @param TokenizeFunction $tokenizeFunction
     * @param RenderFunction   $renderFunction
     * @param TabCode          $tabCode
     */
    public function __construct(
        TokenizeFunction $tokenizeFunction,
        RenderFunction $renderFunction,
        TabCode $tabCode
    ) {
        $this->tokenizeFunction = $tokenizeFunction;
        $this->renderFunction = $renderFunction;
        $this->tabCode = $tabCode;
    }

    /**
     * @param array $functions
     *
     * @return string
     */
    public function render(
        array $functions
    ) {
        $tree = [];
        foreach ($functions as $name => $function) {
            $tree[$name] = sprintf('%s: ', $name);

            if (isset($function['url'])) {
                $tokenization = $this->tokenizeFunction->tokenize(
                    $function
                );

                $tree[$name] .= $this->renderFunction->render(
                    $tokenization
                );
            } else {
                $tree[$name] .= sprintf("%s\n}",
                    $this->tabCode->tab(
                        sprintf("{\n%s", $this->render($function)),
                        1
                    )
                );
            }
        }

        $tree = implode(
            ",\n",
            $tree
        );

        return $tree;
    }
}