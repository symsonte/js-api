<?php

namespace Symsonte\JsApi\Test\User;

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
     */
    public function update(
        string $id,
        string $firstname
    ) {

    }
}