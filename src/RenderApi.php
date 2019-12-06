<?php

namespace Symsonte\JsApi;

/**
 * @di\service()
 */
class RenderApi
{
    /**
     * @var ParseCode
     */
    private $parseCode;

    /**
     * @var TokenizeApi
     */
    private $tokenizeApi;

    /**
     * @param ParseCode   $parseCode
     * @param TokenizeApi $tokenizeApi
     */
    public function __construct(
        ParseCode $parseCode,
        TokenizeApi $tokenizeApi
    ) {
        $this->parseCode = $parseCode;
        $this->tokenizeApi = $tokenizeApi;
    }

    /**
     * @param string $prefix
     * @param array  $server
     *
     * @return string
     */
    public function render(
        string $prefix,
        array $server
    ) {
        $functions = $this->parseCode->parse(
            $prefix
        );

        $tokenization = $this->tokenizeApi->tokenize(
            $functions,
            $server
        );

        $imports = implode("\n", $tokenization->imports);

        $locals = implode("\n", $tokenization->locals);

        return sprintf(
            "%s\n\n%s\n\n%s\n\n%s",
            $imports,
            $locals,
            $tokenization->tree,
            $tokenization->export
        );
    }
}