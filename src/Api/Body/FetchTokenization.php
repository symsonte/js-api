<?php

namespace Symsonte\JsApi\Api\Body;

class FetchTokenization
{
    /**
     * @var string
     */
    public $layout;

    /**
     * @var string[]
     */
    public $parameters;

    /**
     * @var string[]
     */
    public $then;

    /**
     * @param string   $layout
     * @param string[] $parameters
     * @param string[] $then
     */
    public function __construct(
        string $layout,
        array $parameters,
        array $then
    ) {
        $this->layout = $layout;
        $this->parameters = $parameters;
        $this->then = $then;
    }
}