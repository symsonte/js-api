<?php

namespace Symsonte\JsApi;

/**
 * @di\service()
 */
class CacheTokenizeApi implements TokenizeApi
{
    /**
     * @var OrdinaryTokenizeApi
     */
    private $tokenizeApi;

    /**
     * @param OrdinaryTokenizeApi $tokenizeApi
     */
    public function __construct(OrdinaryTokenizeApi $tokenizeApi)
    {
        $this->tokenizeApi = $tokenizeApi;
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

        $tokenization->locals[] = "
const hash = (str) => {
    let hash = 0, i, chr;
    
    if (str.length === 0) { 
        return hash;
    }
    
    for (i = 0; i < str.length; i++) {
        chr = str.charCodeAt(i);
    
        hash = ((hash << 5) - hash) + chr;
    
        hash |= 0; // Convert to 32bit integer
    }
    
    return hash;
};
";

        $tokenization->imports[] = "import Platform from \"@yosmy/platform\";";
        $tokenization->imports[] = "import uniq from \"lodash/uniq\";";
        $tokenization->imports[] = "import uniqWith from \"lodash/uniqWith\";";
        $tokenization->imports[] = "import union from \"lodash/union\";";
        $tokenization->imports[] = "import unionBy from \"lodash/unionBy\";";

        return new Tokenization(
            $tokenization->imports,
            $tokenization->locals,
            $tokenization->tree,
            $tokenization->export
        );
    }
}