<?php

namespace Symsonte\JsApi\Test;

use Symsonte\Authorization\RoleCollector as BaseCollector;

/**
 * @di\service({
 *     private: true
 * })
 */
class RoleCollector implements BaseCollector
{
    /**
     * {@inheritdoc}
     */
    public function collect($user)
    {
        return [];
    }
}
