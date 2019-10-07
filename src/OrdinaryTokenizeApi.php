<?php

namespace Symsonte\JsApi;

/**
 * @di\service()
 */
class OrdinaryTokenizeApi implements TokenizeApi
{
    /**
     * @var Api\RenderTree
     */
    private $renderTree;

    /**
     * @var TabCode
     */
    private $tabCode;

    /**
     * @param Api\RenderTree $renderTree
     * @param TabCode        $tabCode
     */
    public function __construct(
        Api\RenderTree $renderTree,
        TabCode $tabCode
    ) {
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
        $imports = [
            "import {api} from \"@yosmy/request\";",
        ];

        $locals = [
            sprintf(
                "const server = __DEV__ ? '%s' : '%s';",
                $server['dev'],
                $server['prod']
            )
        ];

        $tree = $tree = sprintf(
            "const Api = {\n    %s\n};",
            $this->tabCode->tab(
                $this->renderTree->render($functions),
                1
            )
        );

        $export = "export default Api;";

        return new Tokenization(
            $imports,
            $locals,
            $tree,
            $export
        );
    }
}