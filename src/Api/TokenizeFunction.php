<?php

namespace Symsonte\JsApi\Api;

interface TokenizeFunction
{
    /**
     * @param array $function
     *
     * @return Tokenization
     */
    public function tokenize(
        array $function
    );
}