<?php

namespace Symsonte\JsApi;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\OrdinaryApi;
use Symsonte\JsApi\Tokenization;

interface TokenizeApi
{
    /**
     * @param array $functions
     * @param array $server
     *
     * @return Tokenization
     */
    public function tokenize(
        array $functions,
        array $server
    );
}