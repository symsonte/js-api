<?php

namespace Symsonte\JsApi\Play\User;

use Symsonte\JsApi\Play;

/**
 * @di\service()
 */
class UpdateFirstname
{
    /**
     * @http\resolution({method: "POST", path: "/user/update-firstname"})
     *
     * @param string $id
     * @param string $firstname
     *
     * @throws Play\NonexistentUserException
     */
    public function update(
        string $id,
        string $firstname
    ) {
        throw new Play\NonexistentUserException();
    }
}