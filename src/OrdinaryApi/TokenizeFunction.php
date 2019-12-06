<?php

namespace Symsonte\JsApi\OrdinaryApi;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\TabCode;

/**
 * @di\service()
 */
class TokenizeFunction
{
    /**
     * @var TabCode
     */
    private $tabCode;

    /**
     * @param TabCode $tabCode
     */
    public function __construct(TabCode $tabCode)
    {
        $this->tabCode = $tabCode;
    }

    /**
     * @param array $function
     *
     * @return Api\Tokenization
     */
    public function tokenize(
        array $function
    ) {
        $parameters = $this->buildParameters($function);

        $body = new Api\BodyTokenization(
            [
                'resolve' => $this->renderResolve($function),
                'fetch' => $this->buildFetch($function)
            ]
        );

        return new Api\Tokenization(
            $parameters,
            $body
        );
    }

    /**
     * @param $function
     *
     * @return array
     */
    public function buildParameters($function)
    {
        $parameters = [];

        if ($function['auth'] == true) {
            $parameters[] = 'token';
        }

        if (count($function['parameters']) > 0) {
            foreach ($function['parameters'] as $parameter) {
                $parameters[$parameter] = sprintf("%s", $parameter);
            }
        }

        $parameters[] = 'onReturn';

        if (count($function['exceptions']) > 0) {
            foreach ($function['exceptions'] as $exception) {
                $name = sprintf('on%s', $exception['name']);

                $parameters[] = $name;
            }
        }

        $parameters[] = 'onUnknownException';
        $parameters[] = 'onConnectionException';
        $parameters[] = 'onServerException';

        if (in_array("http\\request\\device", $function['domains'])) {
            array_unshift($parameters, 'device');
        }

        return $parameters;
    }

    /**
     * @param array $function
     *
     * @return string
     */
    public function renderResolve(array $function)
    {
        $exceptions = [];

        if (count($function['exceptions']) > 0) {
            foreach ($function['exceptions'] as $exception) {
                $name = sprintf('on%s', $exception['name']);

                $exception = sprintf(
                    "case '%s':
    %s(payload);
    
    break;",
                    $exception['code'],
                    $name
                );

                $exceptions[] = $exception;
            }
        }

        $exceptions = implode("\n", $exceptions);

        $exceptions = sprintf("%s\n", $exceptions);

        return sprintf("const resolve = (response, %s) => {
    const {code, payload} = response;

    switch (code) {
        case 'return':
            onReturn(payload);

            break;
        %sdefault:
            onUnknownException(response);
    }
};",
            $this->renderCases($function),
            $this->tabCode->tab(
                $exceptions,
                2
            )
        );
    }

    /**
     * @param array $function
     *
     * @return Api\Func\Body\FetchTokenization
     */
    public function buildFetch(array $function)
    {
        $parameters = [
            'server' => sprintf("server + '%s'", $function['url']),
            'device' => in_array("http\\request\\device", $function['domains'])
                ? 'device'
                : 'null',
            'token' => $function['auth'] == true
                ? 'token'
                : 'null',
            'payload' => $this->renderPayload($function)
        ];

        $then = [];

        // TODO: Move out to an independent TokenizeFunction
        if ($function['cacheUnset']) {
            foreach ($function['cacheUnset']['routes'] as $route) {
                $hash = [];
                foreach ($route['parameters'] as $parameter) {
                    if (
                        $parameter == 'user'
                        && in_array(
                            'http\request\user',
                            $function['domains']
                        )
                    ) {
                        $parameter = 'token';
                    }

                    $hash[] = sprintf('${hash(%s)}', $parameter);
                }

                $then[] = sprintf(
                    "Platform.cache.delete(`%s-%s`).catch(console.log);\n",
                    $route['route'],
                    implode('-', $hash)
                );
            }
        }

        $then['resolve'] = sprintf(
            "resolve(response, %s);",
            $this->renderCases($function)
        );

        return new Api\Func\Body\FetchTokenization(
            $parameters,
            $then
        );
    }

    /**
     * @param array $function
     *
     * @return string
     */
    public function renderCases(array $function)
    {
        $calls = ['onReturn'];

        foreach ($function['exceptions'] as $exception) {
            $calls[] = sprintf('on%s', $exception['name']);
        }

        $calls[] = 'onUnknownException';

        $calls = implode(', ', $calls);

        return $calls;
    }

    /**
     * @param $function
     *
     * @return array
     */
    private function renderPayload($function)
    {
        $parameters = [];

        if (count($function['parameters']) > 0) {
            foreach ($function['parameters'] as $parameter) {
                $parameters[$parameter] = sprintf("%s: %s", $parameter, $parameter);
            }
        }

        return $parameters;
    }
}