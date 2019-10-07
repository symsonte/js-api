<?php

namespace Symsonte\JsApi\Api;

use Symsonte\JsApi\OrdinaryApi;
use Symsonte\JsApi\StaticCacheApi;
use Symsonte\JsApi\CollectCacheApi;
use Symsonte\JsApi\UpdatedCollectCacheApi;

/**
 * @di\service()
 */
class DelegatorTokenizeFunction implements TokenizeFunction
{
    /**
     * @var OrdinaryApi\TokenizeFunction
     */
    private $ordinaryTokenizeFunction;

    /**
     * @var StaticCacheApi\TokenizeFunction
     */
    private $staticCacheTokenizeFunction;

    /**
     * @var CollectCacheApi\TokenizeFunction
     */
    private $collectCacheTokenizeFunction;

    /**
     * @var UpdatedCollectCacheApi\TokenizeFunction
     */
    private $updatedCollectCacheTokenizeFunction;

    /**
     * @param OrdinaryApi\TokenizeFunction            $ordinaryTokenizeFunction
     * @param StaticCacheApi\TokenizeFunction         $staticCacheTokenizeFunction
     * @param CollectCacheApi\TokenizeFunction        $collectCacheTokenizeFunction
     * @param UpdatedCollectCacheApi\TokenizeFunction $updatedCollectCacheTokenizeFunction
     */
    public function __construct(
        OrdinaryApi\TokenizeFunction $ordinaryTokenizeFunction,
        StaticCacheApi\TokenizeFunction $staticCacheTokenizeFunction,
        CollectCacheApi\TokenizeFunction $collectCacheTokenizeFunction,
        UpdatedCollectCacheApi\TokenizeFunction $updatedCollectCacheTokenizeFunction
    ) {
        $this->ordinaryTokenizeFunction = $ordinaryTokenizeFunction;
        $this->staticCacheTokenizeFunction = $staticCacheTokenizeFunction;
        $this->collectCacheTokenizeFunction = $collectCacheTokenizeFunction;
        $this->updatedCollectCacheTokenizeFunction = $updatedCollectCacheTokenizeFunction;
    }

    /**
     * {@inheritDoc}
     */
    public function tokenize(
        array $function
    ) {
        if ($function['cacheSet']) {
            if (
                !isset($function['cacheSet']['type'])
                || $function['cacheSet']['type'] == 'static'
            ) {
                return $this->staticCacheTokenizeFunction->tokenize($function);
            }

            if ($function['cacheSet']['type'] == 'collect') {
                return $this->collectCacheTokenizeFunction->tokenize($function);
            }

            if ($function['cacheSet']['type'] == 'updated-collect') {
                return $this->updatedCollectCacheTokenizeFunction->tokenize($function);
            }
        }

        return $this->ordinaryTokenizeFunction->tokenize($function);
    }
}