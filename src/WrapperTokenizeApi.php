<?php

namespace Symsonte\JsApi;

use Symsonte\JsApi\WrapperApi\RenderTree;

/**
 * @di\service()
 */
class WrapperTokenizeApi implements TokenizeApi
{
    /**
     * @var CacheTokenizeApi
     */
    private $tokenizeApi;

    /**
     * @var RenderTree
     */
    private $renderTree;

    /**
     * @var TabCode
     */
    private $tabCode;

    /**
     * @param CacheTokenizeApi $tokenizeApi
     * @param RenderTree       $renderTree
     * @param TabCode          $tabCode
     */
    public function __construct(
        CacheTokenizeApi $tokenizeApi,
        RenderTree $renderTree,
        TabCode $tabCode
    ) {
        $this->tokenizeApi = $tokenizeApi;
        $this->renderTree = $renderTree;
        $this->tabCode = $tabCode;
    }

    /**
     * {@inheritDoc}
     */
    public function tokenize(
        array $functions,
        array $server
    ) {
        $tokenization = $this->tokenizeApi->tokenize(
            $functions,
            $server
        );

        $wrapper = sprintf(
            "const WrappedApi = (
    session, 
    token, 
    onConnectionException,
    onServerException,
    onUnknownException
) => {
    return {
        %s
    };
};",
            $this->tabCode->tab(
                $this->renderTree->render($functions, $functions),
                2
            )
        );

        $tree = sprintf(
            "%s\n\n%s",
            $tokenization->tree,
            $wrapper
        );

        $export = "export default WrappedApi;";

        return new Tokenization(
            $tokenization->imports,
            $tokenization->locals,
            $tree,
            $export
        );
    }
}