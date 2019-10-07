<?php

namespace Symsonte\JsApi\UpdatedCollectCacheApi;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\OrdinaryApi;
use LogicException;

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

        unset($tokenization->parameters[$function['cacheSet']['updated']]);

        /** @var Api\Body\FetchTokenization $fetch */
        $fetch = $tokenization->body->items['fetch'];

        $fetch->parameters['payload'][$function['cacheSet']['updated']] = 'updated: last';

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
            $function['cacheSet']['keys'][0],
            $this->calculateExpiry($function['cacheSet']['expiry']),
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
        // Priority order: cache, response, nonexistent
        
        table = file.table
            .concat(
                response.payload.map(({%3$s}) => {
                    return %3$s;
                })
            )
            .concat(
                %2$s
            );
        
        response.payload = file.response.payload
            .concat(
                response.payload
            );
    }
    // Cache expired
    else 
    {
        // Priority order: response, cache
        
        table = response.payload.map(({%3$s}) => {
            return %3$s;
        })
            .concat(
                file.table
            );
        
        response.payload = response.payload.concat(
            file.response.payload
        );
    }
} else {
    // Priority order: response, nonexistent

    table = response.payload
        .map(({%3$s}) => {
            return %3$s;
        })
        .concat(
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
                $this->calculateExpiry($function['cacheSet']['expiry']),
                $function['cacheSet']['parameter'],
                $function['cacheSet']['keys'][0],
                $function['url']
            )
        );

        $tokenization->body->items['fetch'] = $fetch;

        return $tokenization;
    }

    /**
     * @param string $text
     *
     * @return int
     */
    private function calculateExpiry($text)
    {
        switch ($text) {
            case '1 minute':
                return 60000;
            case '1 week':
                return 604800000;
            default:
                throw new LogicException($text);
        }
    }
}