<?php

namespace Symsonte\JsApi;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Object_;
use Symsonte\AuthorizationChecker;
use Symsonte\Http\Resolution\Finder;
use Symsonte\Service\Container;
use ReflectionClass;
use ReflectionException;
use LogicException;

/**
 * @di\service()
 */
class GenerateCode
{
    /**
     * @var Finder
     */
    private $controllerFinder;

    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var Container
     */
    private $serviceContainer;

    /**
     * @var RenderCode
     */
    private $renderCode;

    /**
     * @param Finder               $controllerFinder
     * @param AuthorizationChecker $authorizationChecker
     * @param Container            $serviceContainer
     * @param RenderCode           $renderCode
     *
     * @di\arguments({
     *     serviceContainer: "@symsonte.service.container"
     * })
     */
    public function __construct(
        Finder $controllerFinder,
        AuthorizationChecker $authorizationChecker,
        Container $serviceContainer,
        RenderCode $renderCode
    ) {
        $this->controllerFinder = $controllerFinder;
        $this->authorizationChecker = $authorizationChecker;
        $this->serviceContainer = $serviceContainer;
        $this->renderCode = $renderCode;
    }

    /**
     * @param string $prefix
     * @param array  $server
     *
     * @return string
     */
    public function generate(
        string $prefix,
        array $server
    ) {
//        $prefix = sprintf('%s\\', $prefix);

        $controllers = $this->controllerFinder->all();

        ksort($controllers);

        $all = [
            'root' => [],
            'inside' => []
        ];
        foreach ($controllers as $url => $controller) {
            $auth = $this->authorizationChecker->has($controller);

            list($controller, $method) = explode(':', $controller);

            $controller = $this->serviceContainer->get($controller);

            try {
                $reflector = new ReflectionClass($controller);
            } catch (ReflectionException $e) {
                throw new LogicException(null, null, $e);
            }

            try {
                $docBlock = (DocBlockFactory::createInstance())->create(
                    $reflector->getMethod($method)->getDocComment()
                );
            } catch (ReflectionException $e) {
                throw new LogicException(null, null, $e);
            }

            $cache = null;
            if ($docBlock->hasTag('cache')) {
                /** @var Generic $tag */
                $tag = $docBlock->getTagsByName('cache')[0];
                /** @var Description $description */
                $cache = $tag->getDescription()->render();
                $cache = str_replace(['(', ')'], '', $cache);
                $cache = json_decode($cache, true);
                $cache = $cache['expiry'];
            }

            $exceptions = [];
            if ($docBlock->hasTag('throws')) {
                /** @var Throws[] $tags */
                $tags = $docBlock->getTagsByName('throws');
                foreach ($tags as $tag) {
                    /** @var Object_ $type */
                    $type = $tag->getType();

                    $exceptions[] = [
                        'code' => $this->generateExceptionCode(
                            $prefix,
                            $reflector->getNamespaceName(),
                            $type->getFqsen()
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
                    $parameters[$tag->getVariableName()] = $tag->getVariableName();
                }
            }

            /* Remove domain parameters, like ip and session */

            if ($docBlock->hasTag('domain\parameter')) {
                /** @var Param[] $tags */
                $tags = $docBlock->getTagsByName('domain\parameter');
                foreach ($tags as $tag) {
                    $description = (string) $tag->getDescription();
                    $description = substr(strstr($description, '('), 1, -1);
                    $description = json_decode($description, true);

                    if (!in_array(
                        $description['value'],
                        [
                            'http\request\ip',
                            'http\request\user',
                            'http\request\session',
                        ]
                    )) {
                        continue;
                    }

                    unset($parameters[$description['name']]);
                }
            }

            // Split namespace
            $parts = explode(
                '\\',
                str_replace(
                    sprintf("%s\\", $prefix),
                    '',
                    $reflector->getName()
                )
            );

            $functions = $this->resolve(
                $parts,
                [
                    'url' => $url,
                    'session' => 'session',
                    'auth' => $auth,
                    'parameters' => $parameters,
                    'exceptions' => $exceptions,
                    'cache' => $cache
                ]
            );

            $current = current($functions);

            // Is a function at root level?
            if (isset($current['url'])) {
                $all['root'] = array_merge(
                    $all['root'],
                    $functions
                );
            }
            // Is a function from inside
            else {
                $all['inside'] = array_merge_recursive(
                    $all['inside'],
                    $functions
                );
            }

            unset($current);
        }

        $functions = array_merge(
            $all['root'],
            $all['inside']
        );

        $render = $this->renderCode->render($functions, $server);

        return $render;
    }

    /**
     * @param array $parts
     * @param array $data
     *
     * @return array
     */
    private function resolve($parts, $data)
    {
        // Is a function from inside?
        if (count($parts) > 1) {
            $part = array_shift($parts);

            // Recursive call
            $function[lcfirst($part)] = $this->resolve($parts, $data);

            return $function;
        }

        // Is a function at root level

        $function[$this->generateName($data['url'], $parts[0])] = $data;

        return $function;
    }

    /**
     * Generates a code for given exception based on its name
     *
     * @param string $prefix
     * @param string $name
     * @param string $exception
     *
     * @return string
     */
    private function generateExceptionCode(
        string $prefix,
        string $namespace,
        string $exception
    ) {
        $exception = sprintf('%s%s', $namespace, $exception);

        $exception = str_replace(sprintf("%s\\", $prefix), '', $exception);

        $exception = strtr(
            preg_replace('/(?<=[a-zA-Z0-9])[A-Z]/', '-\\0', $exception),
            '\\',
            '.'
        );

        $exception = strtolower($exception);

        return $exception;
    }

    /**
     * @param string $url
     * @param string $name
     *
     * @return string
     */
    private function generateName(string $url, string $name)
    {
        $url = substr($url, strrpos($url, '/') + 1);

        $url = str_replace('-', ' ', $url);

        $url = ucwords($url);

        $url = str_replace(' ', '', $url);

        $url = lcfirst($url);

        if ($url === '') {
            $url = lcfirst($name);
        }

        return $url;
    }
}