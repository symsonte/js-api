<?php

namespace Symsonte\JsApi\Play;

/**
 * @di\service()
 */
class CollectUpdatedUsers
{
    /**
     * @http\resolution({method: "POST", path: "/collect-updated-users"})
     * @cache\set({"type": "updated-collect", "parameter": "ids", "keys": ["id"], "updated": "updated", "expiry": "1 minute"})
     * @domain\authorization({roles: ["owner", "operator"]})
     *
     * @param string[] $ids
     * @param int      $updated
     *
     * @return array
     */
    public function collect(
        array $ids,
        int $updated
    ) {
        return [];
    }
}