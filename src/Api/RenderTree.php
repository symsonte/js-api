<?php

namespace Symsonte\JsApi\Api;

use Symsonte\JsApi\TabCode;

/**
 * @di\service()
 */
class RenderTree
{
    /**
     * @var DelegatorRenderFunction
     */
    private $renderFunction;

    /**
     * @var TabCode
     */
    private $tabCode;

    /**
     * @param DelegatorRenderFunction $renderFunction
     * @param TabCode                 $tabCode
     */
    public function __construct(
        DelegatorRenderFunction $renderFunction,
        TabCode $tabCode
    ) {
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
                $tree[$name] .= $this->renderFunction->render(
                    $function
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