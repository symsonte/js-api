<?php

namespace Symsonte\JsApi;

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