<?php

namespace Symsonte\JsApi\CollectCacheApi;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\OrdinaryApi;

/**
 * @di\service()
 */
class TokenizeFunction implements Api\TokenizeFunction
{
    /**
     * @var OrdinaryApi\TokenizeFunction
     */
    private $tokenizeFunction;

    /**
     * @param OrdinaryApi\TokenizeFunction $tokenizeFunction
     */
    public function __construct(
        OrdinaryApi\TokenizeFunction $tokenizeFunction
    ) {
        $this->tokenizeFunction = $tokenizeFunction;
    }

    /**
     * @param array $function
     *
     * @return Api\Tokenization
     */
    public function tokenize(
        array $function
    ) {
        $tokenization = $this->tokenizeFunction->tokenize(
            $function
        );

        /** @var Api\Body\FetchTokenization $fetch */
        $fetch = $tokenization->body->items['fetch'];

        $hash = [];
        foreach ($function['cacheSet']['keys'] as $key) {
            $hash[] = sprintf('${%s}', $key);
        }

        $fetch->layout = sprintf('
Platform.cache.get(`%1$s`)
    .then((file) => {
        if (file) {
            const {table, response} = file;
            
            // Just get ids with no cache
            %2$s = %2$s.filter((%3$s) => {
                return table.indexOf(`%4$s`) === -1
            });
            
            // All in cache?
            if (%2$s.length === 0) {
                %5$s

                return;
            }
        }
        
        %%s
    });
',
            $function['url'],
            $function['cacheSet']['parameter'],
            count($function['cacheSet']['keys']) == 1
                ? $function['cacheSet']['keys'][0]
                : sprintf('{%s}', implode(', ', $function['cacheSet']['keys'])),
            implode('-', $hash),
            $fetch->then['resolve']
        );

        array_unshift(
            $fetch->then,
            sprintf('
// Will contain ids, even nonexistent, as a registry of what cache offers
let table;
    
if (file) {
    // Priority order: cache, request

    %1$s = %1$s.map((%2$s) => {
        return `%3$s`
    });
    
    table = file.table
        .concat(
            %1$s
        );
        
    response.payload = file.response.payload
        .concat(
            response.payload
        );
} else {
    // Priority order: request

    table = %1$s
}
    
Platform.cache.set(`%4$s`, {table: table, response: response}).catch(console.log);
',
                $function['cacheSet']['parameter'],
                count($function['cacheSet']['keys']) == 1
                    ? $function['cacheSet']['keys'][0]
                    : sprintf('{%s}', implode(', ', $function['cacheSet']['keys'])),
                implode('-', $hash),
                $function['url']
            )
        );

        $tokenization->body->items['fetch'] = $fetch;

        return $tokenization;
    }
}