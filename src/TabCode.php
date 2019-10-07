<?php

namespace Symsonte\JsApi;

/**
 * @di\service()
 */
class TabCode
{
    /**
     * @param string $render
     * @param int    $c
     *
     * @return string
     */
    public function tab(string $render, int $c)
    {
        $render = str_replace(
            "\n",
            sprintf("\n%s", str_repeat('    ', $c)),
            $render
        );

        return $render;
    }
}