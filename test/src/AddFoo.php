<?php

namespace Symsonte\JsApi;

/**
 * @di\service()
 */
class AddFoo
{
    /**
     * @http\resolution({method: "POST", path: "/add-foo"})
     *
     * @param string $name
     *
     * @param string $price
     *
     * @return string
     *
     * @throws FooException
     * @throws BarException
     */
    public function add(
        string $name,
        string $price
    ) {
        return "Foo added correctly";
    }
}