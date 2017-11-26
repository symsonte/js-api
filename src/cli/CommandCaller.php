<?php

namespace Symsonte\JsApi\Cli;

use Symsonte\Cli\Server\CommandCaller as BaseCommandCaller;

/**
 * @di\service({
 *     private: true
 * })
 */
class CommandCaller implements BaseCommandCaller
{
    /**
     * {@inheritdoc}
     */
    public function call($command, $method, $parameters)
    {
        try {
            return call_user_func_array([$command, $method], $parameters);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
