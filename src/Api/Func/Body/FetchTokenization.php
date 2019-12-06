<?php

namespace Symsonte\JsApi\Api\Func\Body;

class FetchTokenization
{
    /**
     * @var string[]
     */
    public $parameters;

    /**
     * @var string[]
     */
    public $then;

    /**
     * @param string[] $parameters
     * @param string[] $then
     */
    public function __construct(
        array $parameters,
        array $then
    ) {
        $this->parameters = $parameters;
        $this->then = $then;
    }
}