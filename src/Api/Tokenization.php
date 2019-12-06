<?php

namespace Symsonte\JsApi\Api;

class Tokenization
{
    /**
     * @var string[]
     */
    public $parameters;

    /**
     * @var BodyTokenization
     */
    public $body;

    /**
     * @param string[]         $parameters
     * @param BodyTokenization $body
     */
    public function __construct(
        array $parameters,
        BodyTokenization $body
    ) {
        $this->parameters = $parameters;
        $this->body = $body;
    }
}