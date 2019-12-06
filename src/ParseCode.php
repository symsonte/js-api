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
class ParseCode
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
     * @param Finder               $controllerFinder
     * @param AuthorizationChecker $authorizationChecker
     * @param Container            $serviceContainer
     *
     * @di\arguments({
     *     serviceContainer: "@symsonte.service.container"
     * })
     */
    public function __construct(
        Finder $controllerFinder,
        AuthorizationChecker $authorizationChecker,
        Container $serviceContainer
    ) {
        $this->controllerFinder = $controllerFinder;
        $this->authorizationChecker = $authorizationChecker;
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    public function parse(
        string $prefix
    ) {
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

            if (strpos($reflector->getNamespaceName(), $prefix) === false) {
                continue;
            }

            $cacheSet = false;
            if ($docBlock->hasTag('cache\set')) {
                /** @var Generic $tag */
                $tag = $docBlock->getTagsByName('cache\set')[0];
                /** @var Description $description */
                $cacheSet = $tag->getDescription()->render();
                $cacheSet = str_replace(['(', ')'], '', $cacheSet);
                $cacheSet = json_decode($cacheSet, true);
            }

            $cacheUnset = null;
            if ($docBlock->hasTag('cache\unset')) {
                /** @var Generic $tag */
                $tag = $docBlock->getTagsByName('cache\unset')[0];
                /** @var Description $description */
                $cacheUnset = $tag->getDescription()->render();
                $cacheUnset = str_replace(['(', ')'], '', $cacheUnset);
                $cacheUnset = json_decode($cacheUnset, true);
            }

            $exceptions = [];
            if ($docBlock->hasTag('throws')) {
                /** @var Throws[] $tags */
                $tags = $docBlock->getTagsByName('throws');
                foreach ($tags as $tag) {
                    /** @var Object_ $type */
                    $type = $tag->getType();

                    $class = sprintf(
                        "%s%s",
                        $reflector->getNamespaceName(),
                        $type->getFqsen()
                    );

                    if (!class_exists($class)) {
                        $class = null;

                        $useStatements = $this->getUseStatements($reflector);

                        foreach ($useStatements as $useStatement) {
                            $parts = explode("\\", (string) $type->getFqsen());
                            unset($parts[0], $parts[1]);
                            $class = implode("\\", $parts);
                            unset($parts);

                            $class = sprintf(
                                "%s\\%s",
                                $useStatement['as'],
                                $class
                            );

                            if (class_exists($class)) {
                                break;
                            }
                        }

                        if ($class === null) {
                            throw new LogicException($type->getFqsen());
                        }
                    }

                    [$code, $name] = $this->generateExceptionCodeAndName(
                        $prefix,
                        $class
                    );

                    $exceptions[] = [
                        'code' => $code,
                        'name' => $name
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

            $domains = [];
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

                    $domains[] = $description['value'];

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
                    'auth' => $auth,
                    'domains' => $domains,
                    'parameters' => $parameters,
                    'exceptions' => $exceptions,
                    'cacheSet' => $cacheSet,
                    'cacheUnset' => $cacheUnset
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

        return $functions;
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
     * @param string $class
     *
     * @return array
     */
    private function generateExceptionCodeAndName(
        string $prefix,
        string $class
    ) {
        $name = str_replace(sprintf("%s\\", $prefix), '', $class);

        $code = strtolower(
            strtr(
                preg_replace(
                    '/(?<=[a-zA-Z0-9])[A-Z]/',
                    '-\\0',
                    $name
                ),
                '\\',
                '.'
            )
        );

        $name = str_replace('\\', '', $name);

        return [$code, $name];
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

    /**
     * Parse class file and get use statements from current namespace
     *
     * @param ReflectionClass $reflector
     *
     * @return array
     */
    private function getUseStatements(ReflectionClass $reflector)
    {
        $source = $this->readFileSource($reflector);

        return $this->tokenizeSource($source, $reflector);
    }

    /**
     * Read file source up to the line where our class is defined.
     *
     * @return string
     */
    private function readFileSource(ReflectionClass $reflector)
    {
        $file = fopen($reflector->getFileName(), 'r');
        $line = 0;
        $source = '';

        while (!feof($file)) {
            ++$line;

            if ($line >= $reflector->getStartLine()) {
                break;
            }

            $source .= fgets($file);
        }

        fclose($file);

        return $source;
    }

    /**
     * Parse the use statements from read source by
     * tokenizing and reading the tokens. Returns
     * an array of use statements and aliases.
     *
     * @param string          $source
     * @param ReflectionClass $reflector
     *
     * @return array
     */
    private function tokenizeSource(
        string $source,
        ReflectionClass $reflector
    ) {
        $tokens = token_get_all($source);

        $builtNamespace = '';
        $buildingNamespace = false;
        $matchedNamespace = false;

        $useStatements = [];
        $record = false;
        $currentUse = [
            'class' => '',
            'as' => ''
        ];

        foreach ($tokens as $token) {
            if ($token[0] === T_NAMESPACE) {
                $buildingNamespace = true;

                if ($matchedNamespace) {
                    break;
                }
            }

            if ($buildingNamespace) {
                if ($token === ';') {
                    $buildingNamespace = false;
                    continue;
                }

                switch ($token[0]) {
                    case T_STRING:
                    case T_NS_SEPARATOR:
                        $builtNamespace .= $token[1];
                        break;
                }

                continue;
            }

            if ($token === ';' || !is_array($token)) {
                if ($record) {
                    $useStatements[] = $currentUse;
                    $record = false;
                    $currentUse = [
                        'class' => '',
                        'as' => ''
                    ];
                }

                continue;
            }

            if ($token[0] === T_CLASS) {
                break;
            }

            if (strcasecmp($builtNamespace, $reflector->getNamespaceName()) === 0) {
                $matchedNamespace = true;
            }

            if ($matchedNamespace) {
                if ($token[0] === T_USE) {
                    $record = 'class';
                }

                if ($token[0] === T_AS) {
                    $record = 'as';
                }

                if ($record) {
                    switch ($token[0]) {
                        case T_STRING:
                        case T_NS_SEPARATOR:
                            if ($record) {
                                $currentUse[$record] .= $token[1];
                            }

                            break;
                    }
                }
            }

            if ($token[2] >= $reflector->getStartLine()) {
                break;
            }
        }

        // Make sure the as key has the name of the class even
        // if there is no alias in the use statement.
        foreach ($useStatements as &$useStatement) {
            if (empty($useStatement['as'])) {
                $useStatement['as'] = basename($useStatement['class']);
            }
        }

        return $useStatements;
    }
}