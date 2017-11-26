<?php

namespace Symsonte\JsApi;

use Symsonte\Http\Server\Request\Resolution\NikicFastRouteFinder;
use Symsonte\Service\Container;

/**
 * @di\service()
 */
class GenerateFiles
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
     * @cli\resolution({command: "/generate-files"})
     */
    public function generate(
    ) {
        $controllers = $this->controllerFinder->all();

        foreach ($controllers as $controller) {
            list($controller, $method) = explode(':', $controller);
            $controller = $this->serviceContainer->get($controller);
        }
    }
}