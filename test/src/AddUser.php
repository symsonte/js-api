<?php

namespace Symsonte\JsApi\Test;

use Symsonte\JsApi\Test\User;

/**
 * @di\service()
 */
class AddUser
{
    /**
     * @http\resolution({method: "POST", path: "/add-user"})
     *
     * @param string $firstname
     * @param string $lastname
     *
     * @return string
     *
     * @throws User\InvalidFirstnameException
     * @throws User\InvalidLastnameException
     */
    public function add(
        string $firstname,
        string $lastname
    ) {
        $id = uniqid();

        if ($firstname) {
            throw new User\InvalidFirstnameException();
        }

        if ($lastname) {
            throw new User\InvalidLastnameException();
        }

        return $id;
    }
}