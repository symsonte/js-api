<?php

namespace Symsonte\JsApi;

use Symsonte\Http\Server\Request\Resolution\NikicFastRouteFinder;
use Symsonte\Service\Container;

/**
 * @di\service()
 */
class AddFoo
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
        string $name, string $price
    ) {
        $controllers = $this->controllerFinder->all();

        foreach ($controllers as $controller) {
            list($controller, $method) = explode(':', $controller);
            $controller = $this->serviceContainer->get($controller);
        }
    }
}