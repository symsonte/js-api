<?php

namespace Symsonte\JsApi;

/**
 * @di\service()
 */
class RenderCode
{
    /**
     * @param array $functions
     * @param array $server
     *
     * @return string
     */
    public function render(array $functions, array $server)
    {
        $cache = $this->checkCache($functions);

        return sprintf(
            "import {api} from \"@yosmy/request\";%s\n\n%s\n\nexport default {\n    %s\n};",
            $cache ? "\nimport {Platform} from \"@yosmy/ui\";" : "",
            sprintf("const server = __DEV__ ? '%s' : '%s';", $server['dev'], $server['prod']),
            $this->renderTree($functions)
        );
    }

    /**
     * @param array $functions
     *
     * @return string
     */
    private function renderTree(array $functions)
    {
        $render = [];
        foreach ($functions as $name => $function) {
            $render[$name] = sprintf('%s: ', $name);

            if (isset($function['url'])) {
                $render[$name] .= $this->renderFunction($function);
            } else {
                $render[$name] .= sprintf("{\n    %s\n}", $this->renderTree($function));
            }
        }

        $render = implode(
            ",\n",
            $render
        );

        $render = $this->tab($render, 1);

        return $render;
    }

    /**
     * @param array $function
     *
     * @return string
     */
    private function renderFunction(array $function)
    {
        $render = sprintf("(
    %s
) => {
    api(
        server + \"%s\",
        %s,
        %s,
        {
            %s
        }%s
    )
        .then((response) => {
            const {code, payload} = response;

            switch (code) {
                case \"return\":
                    onReturn(payload);

                    break;
                %sdefault:
                    onUnknownException(response);
            }
        })
        .catch((response) => {
            onUnknownException(response);
        });
}",
            $this->tab(
                $this->renderParameters($function),
                1
            ),
            $function['url'],
            $function['session'],
            $function['auth'] ? 'token' : 'null',
            $this->tab(
                $this->renderPayload($function),
                3
            ),
            $this->tab(
                $this->renderCache($function),
                2
            ),
            $this->tab(
                $this->renderExceptions($function),
                4
            )
        );

        return $render;
    }

    /**
     * @param $function
     *
     * @return string
     */
    private function renderParameters($function)
    {
        $parameters = [];

        if ($function['auth']) {
            $parameters[] = 'token';
        }

        if (count($function['parameters']) > 0) {
            foreach ($function['parameters'] as $parameter) {
                $parameters[] = sprintf("%s", $parameter);
            }
        }

        $parameters[] = 'onReturn';

        if (count($function['exceptions']) > 0) {
            foreach ($function['exceptions'] as $exception) {
                $name = sprintf('on%s', $exception['name']);
                // Can't delete suffix, because sometimes it's just Exception class name
                // $name = str_replace('Exception', '', $name);

                $parameters[] = $name;
            }
        }

        $parameters[] = 'onUnknownException';

        if (!in_array('session', $parameters)) {
            array_unshift($parameters, 'session');
        }

        $parameters = implode(",\n", $parameters);

        return $parameters;
    }

    /**
     * @param $function
     *
     * @return string
     */
    private function renderPayload($function)
    {
        $parameters = [];

        if (count($function['parameters']) > 0) {
            foreach ($function['parameters'] as $parameter) {
                $parameters[] = sprintf("%s: %s", $parameter, $parameter);
            }
        }

        $parameters = implode(",\n", $parameters);

        return $parameters;
    }

    /**
     * @param $function
     *
     * @return string
     */
    private function renderCache($function)
    {
        if (!$function['cache']) {
            return '';
        }

        return sprintf(
            ",\n{\n    get: Platform.store.get,\n    set: Platform.store.set,\n    expiry: '%s'\n}",
            $function['cache']
        );
    }

    /**
     * @param $function
     *
     * @return string
     */
    private function renderExceptions($function)
    {
        $exceptions = [];

        if (count($function['exceptions']) > 0) {
            foreach ($function['exceptions'] as $exception) {
                $name = sprintf('on%s', $exception['name']);
                // Can't delete suffix, because sometimes it's just Exception class name
                // $name = str_replace('Exception', '', $name);

                $exception = sprintf(
                    "case \"%s\":\n    %s(payload);\n\n    break;",
                    $exception['code'],
                    $name
                );

                $exceptions[] = $exception;
            }
        }

        $exceptions = implode("\n", $exceptions);

        $exceptions = sprintf("%s\n", $exceptions);

        return $exceptions;
    }

    /**
     * @param string $render
     *
     * @return string
     */
    private function tab(string $render, int $c)
    {
        $render = str_replace(
            "\n",
            sprintf("\n%s", str_repeat('    ', $c)),
            $render
        );

        return $render;
    }

    private function checkCache(array $functions)
    {
        foreach ($functions as $function) {
            if (array_key_exists('cache', $function)) {
                if ($function['cache'] !== null) {
                    return true;
                }

                continue;
            }

            $result = $this->checkCache($function);

            if ($result === true) {
                return true;
            }

            continue;
        }

        return false;
    }
}