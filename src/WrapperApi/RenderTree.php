<?php

namespace Symsonte\JsApi\WrapperApi;

use Symsonte\JsApi\TabCode;

/**
 * @di\service()
 */
class RenderTree
{
    /**
     * @var TabCode
     */
    private $tabCode;

    /**
     * @param TabCode $tabCode
     */
    public function __construct(
        TabCode $tabCode
    ) {
        $this->tabCode = $tabCode;
    }

    /**
     * @param array $branch
     * @param array $functions
     *
     * @return string
     */
    public function render(
        array $branch,
        array $functions
    ) {
        $render = [];
        foreach ($branch as $name => $function) {
            $render[$name] = sprintf('%s: ', $name);

            if (isset($function['url'])) {
                $render[$name] .= $this->renderFunction(
                    $function,
                    $functions
                );
            } else {
                $render[$name] .= sprintf(
                    "{\n    %s\n}",
                    $this->tabCode->tab(
                        $this->render(
                            $function,
                            $functions
                        ),
                        1
                    )
                );
            }
        }

        $render = implode(
            ",\n",
            $render
        );

        return $render;
    }

    /**
     * @param array $function
     * @param array $functions
     *
     * @return string
     */
    private function renderFunction(
        array $function,
        array $functions
    ) {
        $path = $this->resolvePath($function, [], $functions);

        $path = implode('.', $path);

        return sprintf("(
    ...props
) => {
    Api.%s(
        %s%s...props,
        onUnknownException,
        onConnectionException,
        onServerException
    )
}",
            $path,
            in_array("http\\request\\device", $function['domains'])
                ? $this->tabCode->tab(
                "device,\n",
                2
            )
                : null,
            $function['auth'] == true
                ? $this->tabCode->tab(
                "token,\n",
                2
            )
                : null
        );
    }

    /**
     * @param array $subject
     * @param array  $path
     * @param array  $functions
     *
     * @return array
     */
    private function resolvePath(
        array $subject,
        array $path,
        array $functions
    ) {
        foreach ($functions as $name => $function) {
            if (isset($function['url'])) {
                if ($function['url'] != $subject['url']) {
                    continue;
                }

                return array_merge(
                    $path,
                    [$name]
                );

                break;
            } else {
                $oldPath = $path;

                $path = $this->resolvePath(
                    $subject,
                    array_merge(
                        $path,
                        [$name]
                    ),
                    $function
                );

                if ($path) {
                    return $path;
                } else {
                    $path = $oldPath;
                }
            }
        }

        return [];
    }
}