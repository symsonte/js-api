<?php

namespace Symsonte\JsApi;

use Symsonte\Http\Server\Request\Resolution\NikicFastRouteFinder;
use Symsonte\Service\Container;

/**
 * @di\service()
 */
class CollectFoos
{
    /**
     * @var NikicFastRouteFinder
     */
    private $controllerFinder;

    /**
     * @var Container
     */
    private $serviceContainer;

    /**
     * @param NikicFastRouteFinder $controllerFinder
     * @param Container $serviceContainer
     *
     * @di\arguments({
     *     serviceContainer: "@symsonte.service_kit.container"
     * })
     */
    public function __construct(NikicFastRouteFinder $controllerFinder, Container $serviceContainer)
    {
        $this->controllerFinder = $controllerFinder;
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * @http\resolution({method: "POST", path: "/collect-foos"})
     *
     */
    public function collect()
    {
        return "This will return all the Foos";
    }
}