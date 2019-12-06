<?php

namespace Symsonte\JsApi\Api;

use Symsonte\JsApi\OrdinaryApi;
use Symsonte\JsApi\StaticCacheApi;
use Symsonte\JsApi\CollectCacheApi;
use Symsonte\JsApi\UpdatedCollectCacheApi;

/**
 * @di\service()
 */
class DelegatorRenderFunction
{
    /**
     * @var OrdinaryApi\RenderFunction
     */
    private $ordinaryRenderFunction;

    /**
     * @var StaticCacheApi\RenderFunction
     */
    private $staticCacheRenderFunction;

    /**
     * @var CollectCacheApi\RenderFunction
     */
    private $collectCacheRenderFunction;

    /**
     * @var UpdatedCollectCacheApi\RenderFunction
     */
    private $updatedCollectCacheRenderFunction;

    /**
     * @param OrdinaryApi\RenderFunction            $ordinaryRenderFunction
     * @param StaticCacheApi\RenderFunction         $staticCacheRenderFunction
     * @param CollectCacheApi\RenderFunction        $collectCacheRenderFunction
     * @param UpdatedCollectCacheApi\RenderFunction $updatedCollectCacheRenderFunction
     */
    public function __construct(
        OrdinaryApi\RenderFunction $ordinaryRenderFunction,
        StaticCacheApi\RenderFunction $staticCacheRenderFunction,
        CollectCacheApi\RenderFunction $collectCacheRenderFunction,
        UpdatedCollectCacheApi\RenderFunction $updatedCollectCacheRenderFunction
    ) {
        $this->ordinaryRenderFunction = $ordinaryRenderFunction;
        $this->staticCacheRenderFunction = $staticCacheRenderFunction;
        $this->collectCacheRenderFunction = $collectCacheRenderFunction;
        $this->updatedCollectCacheRenderFunction = $updatedCollectCacheRenderFunction;
    }

    /**
     * {@inheritDoc}
     */
    public function render(
        array $function
    ) {
        if ($function['cacheSet']) {
            if (
                !isset($function['cacheSet']['type'])
                || $function['cacheSet']['type'] == 'static'
            ) {
                return $this->staticCacheRenderFunction->render($function);
            }

            if ($function['cacheSet']['type'] == 'collect') {
                return $this->collectCacheRenderFunction->render($function);
            }

            if ($function['cacheSet']['type'] == 'updated-collect') {
                return $this->updatedCollectCacheRenderFunction->render($function);
            }
        }

        return $this->ordinaryRenderFunction->render($function);
    }
}