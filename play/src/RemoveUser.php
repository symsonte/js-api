<?php

namespace Symsonte\JsApi\Play;

use Symsonte\JsApi\Play\User;

/**
 * @di\service()
 */
class RemoveUser
{
    /**
     * @http\resolution({method: "POST", path: "/remove-user"})
     * @cache\unset({"routes": [{"route": "/pick-user", "parameters": ["id"]}]})
     *
     * @param string $id
     */
    public function remove(
        string $id
    ) {
    }
}