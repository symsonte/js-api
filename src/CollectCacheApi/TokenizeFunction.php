<?php

namespace Symsonte\JsApi\CollectCacheApi;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\CalculateExpiry;
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
     * @var CalculateExpiry
     */
    private $calculateExpiry;

    /**
     * @param OrdinaryApi\TokenizeFunction $tokenizeFunction
     * @param CalculateExpiry              $calculateExpiry
     */
    public function __construct(
        OrdinaryApi\TokenizeFunction $tokenizeFunction,
        CalculateExpiry $calculateExpiry
    ) {
        $this->tokenizeFunction = $tokenizeFunction;
        $this->calculateExpiry = $calculateExpiry;
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
        const now = Date.now(); 
        
        let last;
        
        if (file) {
            const {date} = file;
            
            // Cache not expired?
            if (date + %4$s >= now) {
                const {table, response} = file;
            
                // Just get ids with no cache
                %2$s = %2$s.filter((%3$s) => {
                    return table.indexOf(%3$s) === -1
                });
                
                // All in cache?
                if (%2$s.length === 0) {
                    %5$s

                    return;
                }
                // Need some ids 
                else {
                    // Ok, will call the api with those ids
                    
                    last = null;
                }
            }
            // Cache expired 
            else {
                last = date;
            }
        } else {
            last = null;
        }
        
        %%s
    });
',
            $function['url'],
            $function['cacheSet']['parameter'],
            count($function['cacheSet']['keys']) == 1
                ? $function['cacheSet']['keys'][0]
                : sprintf('{%s}', implode(', ', $function['cacheSet']['keys'])),
            isset($function['cacheSet']['expiry'])
                ? $this->calculateExpiry->calculate($function['cacheSet']['expiry'])
                : INF,
            $fetch->then['resolve']
        );

        array_unshift(
            $fetch->then,
            sprintf('
// Will contain ids, even nonexistent, as a registry of what cache offers
let table;
    
if (file) {
    const {date} = file;
    
    // Cache not expired?
    if (date + %1$s >= now) {
        // Merge order: cache, response, nonexistent
        
        table = union(
            file.table,
            response.payload.map(({%3$s}) => {
                return %3$s;
            }),
            %2$s
        );
        
        response.payload = union(
            file.response.payload,
            response.payload
        );
    }
    // Cache expired
    else 
    {
        // Merge order: response, cache
        
        table = union(
            response.payload.map(({%3$s}) => {
                return %3$s;
            }),
            file.table
        );
        
        response.payload = union(
            response.payload,
            file.response.payload
        );
    }
} else {
    // Merge order: response, nonexistent

    table = union(
        response.payload.map(({%3$s}) => {
            return %3$s;
        }),
        %2$s
    );
}
    
// Remove duplicated, priority for the first found

table = uniq(table);

response.payload = uniqWith(
    response.payload,
    (a, b) => {
        return a.%3$s === b.%3$s;
    }
);

Platform.cache.set(`%4$s`, {table: table, response: response, date: now}).catch(console.log);
',
                isset($function['cacheSet']['expiry'])
                    ? $this->calculateExpiry->calculate($function['cacheSet']['expiry'])
                    : INF,
                $function['cacheSet']['parameter'],
                $function['cacheSet']['keys'][0],
                $function['url']
            )
        );

        $tokenization->body->items['fetch'] = $fetch;

        return $tokenization;
    }
}