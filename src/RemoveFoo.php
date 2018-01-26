<?php

namespace Symsonte\JsApi;

/**
 * @di\service()
 */
class RemoveFoo
{
    /**
     * @http\resolution({method: "POST", path: "/remove-foo"})
     *
     * @param string $id
     *
     * @return string
     *
     */
    public function remove(
        string $id
    ) {
        return 'Removed Foo with id: '.$id;
    }
}