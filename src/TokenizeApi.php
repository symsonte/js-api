<?php

namespace Symsonte\JsApi;

interface TokenizeApi
{
    /**
     * @param array  $functions
     * @param string $server
     *
     * @return Tokenization
     */
    public function tokenize(
        array $functions,
        string $server
    );
}