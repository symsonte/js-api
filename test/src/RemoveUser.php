<?php

namespace Symsonte\JsApi\Test;

use Symsonte\JsApi\Test\User;

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