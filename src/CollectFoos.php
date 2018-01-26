<?php

namespace Symsonte\JsApi;

/**
 * @di\service()
 */
class CollectFoos
{
    /**
     * @http\resolution({method: "POST", path: "/collect-foos"})
     * @domain\authorization({roles: ["owner", "operator"]})
     *
     */
    public function collect()
    {
        return "This will return all the Foos";
    }
}