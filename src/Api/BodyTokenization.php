<?php

namespace Symsonte\JsApi\Api;

class BodyTokenization
{
    /**
     * @var string[]
     */
    public $items;

    /**
     * @param string[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }
}