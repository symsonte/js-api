<?php

namespace Symsonte\JsApi;

use phpDocumentor\Reflection\DocBlockFactory;
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
    public function generate()
    {
        $controllers = $this->controllerFinder->all();
        $fileString  = "";

        foreach ($controllers as $url => $controller) {
            list($controller, $method) = explode(':', $controller);
            $controller = $this->serviceContainer->get($controller);

            $reflector = new \ReflectionClass($controller);
            $comment   = $reflector->getMethod($method)->getDocComment();

            $factory  = DocBlockFactory::createInstance();
            $docblock = $factory->create($comment);

            $exceptions = [];
            if ($docblock->hasTag('throws')) {
                $tags = $docblock->getTagsByName('throws');
                foreach ($tags as $index => $tag) {
                    $exceptions[] = $tags[$index]->getType()->getFqsen()->getName();
                }
            }

            $parameters = [];
            if ($docblock->hasTag('param')) {
                $tags = $docblock->getTagsByName('param');
                foreach ($tags as $index => $tag) {
                    $parameters[] = $tags[$index]->getVariableName();
                }
            }

            $mustache = new \Mustache_Engine();
            $hasParam = count($parameters) > 0 ? true : false;
            $data     = [];
            if (count($parameters) > 0) {
                foreach ($parameters as $parameter) {
                    $data[] = $parameter." : ".$parameter;
                }
            }
            $apiJs = $mustache->render(
                'const {{method}} = (
        {{#has_param}}
        {{parameters}},
        {{/has_param}}                            
        onSuccess 
) => {
    request(
        \'POST\',
        \'{{ url}}\',
        null,
        {
        {{#has_param}}
        {{data}}
        {{/has_param}}
        },
        (response) => {
            handle(response, [
                {
                    code: \'success\',
                    callback: (payload) => {
                        onSuccess(payload);
                    }
                },
                {{{exceptionSection}}} 
            ]);
        }
    );
};'
                ,
                array(
                    'method'           => $method,
                    'parameters'       => implode(",\n\t", $parameters),
                    'url'              => $url,
                    'has_param'        => $hasParam,
                    'data'             => implode(",\n\t", $data),
                    'exceptionSection' => $this->generateExceptionsJsTemplate($exceptions),
                )
            );

            $fileString .= $apiJs."\n\n";
        }

        return $fileString;
    }

    /**
     * Generates the exception-code based on the exception name thrown
     *
     * @param string $exception
     *
     * @return string
     */
    private function generateExceptionCode(string $exception)
    {
        $exceptionCode = strtolower(str_replace(' ', '-', trim(preg_replace("([A-Z])", " $0", $exception))));

        return $exceptionCode;
    }

    /**
     * Generates the exceptions Js section for the JS-Api Template.
     *
     * @param array $exceptions
     *
     * @return string
     */
    private function generateExceptionsJsTemplate(array $exceptions)
    {
        $template = "";
        if (count($exceptions) > 0) {
            foreach ($exceptions as $key => $exception) {
                $code     = $this->generateExceptionCode($exception);
                $template .= "
                {  
                    code: '$code',
                    callback: () => {
                       on$exception();
                    } 
                }";
                if ($key < count($exceptions) - 1) {
                    $template .= ",";
                }
            }
        }

        return $template;
    }
}