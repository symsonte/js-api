<?php

namespace Symsonte\JsApi;

class Tokenization
{
    /**
     * @var string[]
     */
    public $imports;

    /**
     * @var string[]
     */
    public $locals;

    /**
     * @var string
     */
    public $tree;

    /**
     * @var string
     */
    public $export;

    /**
     * @param string[] $imports
     * @param string[] $locals
     * @param string   $tree
     * @param string   $export
     */
    public function __construct(
        array $imports,
        array $locals,
        string $tree,
        string $export
    ) {
        $this->imports = $imports;
        $this->locals = $locals;
        $this->tree = $tree;
        $this->export = $export;
    }
}