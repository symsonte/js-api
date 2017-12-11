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
        $exceptions = [];
        $parameters = [];

        foreach ($controllers as $url => $controller) {
            list($controller, $method) = explode(':', $controller);
            $controller = $this->serviceContainer->get($controller);

            $reflector = new \ReflectionClass($controller);
            $comment = $reflector->getMethod($method)->getDocComment();

            $factory = DocBlockFactory::createInstance();
            $docblock = $factory->create($comment);

            if ($docblock->hasTag('throws')) {
                $seeTags = $docblock->getTagsByName('throws');
                foreach ($seeTags as $index => $tag) {
                    $exceptions[] = $seeTags[$index]->getType()->getFqsen()->getName();
                }
            }

            if ($docblock->hasTag('param')) {
                $seeTags = $docblock->getTagsByName('param');
                foreach ($seeTags as $index => $tag) {
                    $parameters[] = $seeTags[$index]->getVariableName();
                }
            }

            $mustache = new \Mustache_Engine();
            $hasParam = count($parameters) > 0 ? true : false;
            $hasException = count($exceptions) > 0 ? true : false;
            $loopExeption = array('exception' => $exceptions);
            $data = [];
            foreach ($parameters as $parameter) {
                $data[] = $parameter . " : " . $parameter;
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
                    'method' => $method,
                    'parameters' => implode(",\n\t", $parameters),
                    'url' => $url,
                    'has_param' => $hasParam,
                    'data' => implode(",\n\t", $data),
                    'exceptionSection' => $this->generateExceptionsJsTemplate($exceptions)
                ));
        }

        return $apiJs;
    }

    /**
     * Generates the exception-code based on the exception name thrown
     *
     * @param string $exception
     * @return string
     */
    private function generateExceptionCode(string $exception)
    {
        $exceptionCode = strtolower(str_replace(' ', '-', trim(preg_replace("([A-Z])", " $0", $exception))));

        return $exceptionCode;
    }

    /**
     * Generates the exceptions Js section for the JS-Api Template.
     * @param array $exceptions
     * @return string
     */
    private function generateExceptionsJsTemplate(array $exceptions)
    {
        $template = "";
        if (count($exceptions) > 0) {
            foreach ($exceptions as $key => $exception) {
                $code = $this->generateExceptionCode($exception);
                $template .= "
                {  
                    code: $code,
                    callback: () => {
                    $exception();
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