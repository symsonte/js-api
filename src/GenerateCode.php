<?php

namespace Symsonte\JsApi;

use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Object_;
use Symsonte\Authorization\Checker;
use Symsonte\Http\Server\Request\Resolution\NikicFastRouteFinder;
use Symsonte\Service\Container;

/**
 * @di\service()
 */
class GenerateCode
{
    /**
     * @var NikicFastRouteFinder
     */
    private $controllerFinder;

    /**
     * @var Checker
     */
    private $authorizationChecker;

    /**
     * @var Container
     */
    private $serviceContainer;

    /**
     * @param NikicFastRouteFinder $controllerFinder
     * @param Checker              $authorizationChecker
     * @param Container            $serviceContainer
     *
     * @di\arguments({
     *     serviceContainer: "@symsonte.service_kit.container"
     * })
     */
    public function __construct(
        NikicFastRouteFinder $controllerFinder,
        Checker $authorizationChecker,
        Container $serviceContainer
    ) {
        $this->controllerFinder = $controllerFinder;
        $this->authorizationChecker = $authorizationChecker;
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * @cli\resolution({command: "/generate-code"})
     *
     * @param string $prefix
     *
     * @return string
     */
    public function generate($prefix = null)
    {
        $mustache = new \Mustache_Engine(
            [
                'loader' => new \Mustache_Loader_FilesystemLoader(
                    __DIR__ . '/templates'
                ),
                'partials_loader' => new \Mustache_Loader_FilesystemLoader(
                    __DIR__ . '/templates/partials')
            ]
        );

        $controllers = $this->controllerFinder->all();

        $functions = [];
        foreach ($controllers as $url => $controller) {
            $auth = $this->authorizationChecker->has($controller);

            list($controller, $method) = explode(':', $controller);

            $controller = $this->serviceContainer->get($controller);

            $reflector = new \ReflectionClass($controller);

            $name = $this->generateNameCode(
                $reflector->getName(),
                $prefix
            );

            $docBlock = (DocBlockFactory::createInstance())->create(
                $reflector->getMethod($method)->getDocComment()
            );

            $exceptions = [];
            if ($docBlock->hasTag('throws')) {
                /** @var Throws[] $tags */
                $tags = $docBlock->getTagsByName('throws');
                foreach ($tags as $tag) {
                    /** @var Object_ $type */
                    $type = $tag->getType();

                    $exceptions[] = [
                        'code' => $this->generateExceptionCode(
                            $type->getFqsen()->getName()
                        ),
                        'name' => $type->getFqsen()->getName()
                    ];
                }
            }

            $parameters = [];
            if ($docBlock->hasTag('param')) {
                /** @var Param[] $tags */
                $tags = $docBlock->getTagsByName('param');
                foreach ($tags as $tag) {
                    $parameters[] = $tag->getVariableName();
                }
            }

            $functions[] = [
                'name' => $name,
                'url' => $url,
                'auth' => $auth,
                'parameters' => $parameters,
                'exceptions' => $exceptions,
            ];
        }

        $api = $mustache->render(
            'root',
            [
                'functions' => $functions,
            ]
        );

        return $api;
    }

    /**
     * @param string $name
     * @param string $prefix
     *
     * @return string
     */
    private function generateNameCode($name, $prefix) {
        $name = str_replace("\\", '', $name);

        $name = str_replace($prefix, '', $name);

        return $name;
    }

    /**
     * Generates a code for given exception based on its name
     *
     * @param string $exception
     *
     * @return string
     */
    private function generateExceptionCode(string $exception)
    {
        $exception = str_replace(
            ' ', '-', trim(preg_replace("([A-Z])", " $0", $exception))
        );

        $exception = str_replace(
            '-Exception', '', $exception
        );

        $exception = strtolower(
            $exception
        );

        return $exception;
    }

}