<?php

namespace Symsonte\JsApi\Play;

/**
 * @di\service()
 */
class PickUser
{
    /**
     * @http\resolution({method: "POST", path: "/pick-user"})
     * @cache\set({"type": "static", "parameters": ["id"]})
     * @domain\authorization({roles: ["owner", "operator"]})
     *
     * @param string $id
     *
     * @return array
     */
    public function collect(
        string $id
    ) {
        return [];
    }
}