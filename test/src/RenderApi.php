<?php

namespace Symsonte\JsApi\Test;

use Symsonte\JsApi;

/**
 * @di\service()
 */
class RenderApi
{
    /**
     * @var JsApi\RenderApi
     */
    private $renderApi;

    /**
     * @param JsApi\RenderApi $renderApi
     */
    public function __construct(
        JsApi\RenderApi $renderApi
    ) {
        $this->renderApi = $renderApi;
    }

    /**
     * @cli\resolution({command: "/render-api"})
     */
    public function render()
    {
        $render = $this->renderApi->render(
            'Symsonte\JsApi\Test',
            [
                'dev' => 'http://192.168.1.14',
                'prod' => 'https://api.prod.com'
            ]
        );

        file_put_contents('Api.js', $render);
    }
}