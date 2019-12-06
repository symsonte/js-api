<?php

namespace Symsonte\JsApi\Play;

/**
 * @di\service()
 */
class CollectUsers
{
    /**
     * @http\resolution({method: "POST", path: "/collect-users"})
     * @cache\set({"type": "collect", "parameter": "ids", "keys": ["id"], "expiry": "1 minute"})
     * @domain\authorization({roles: ["owner", "operator"]})
     *
     * @param string[] $ids
     *
     * @return array
     */
    public function collect(
        array $ids
    ) {
        return [];
    }
}