<?php

namespace Symsonte\JsApi;

use Symsonte\JsApi\Api;
use Symsonte\JsApi\OrdinaryApi;
use LogicException;

/**
 * @di\service()
 */
class CalculateExpiry
{
    /**
     * @param string $text
     *
     * @return int
     */
    public function calculate($text)
    {
        $parts = explode(' ', $text);

        $factor = null;

        if ($parts[1] == 'second' || $parts[1] == 'seconds') {
            $factor = 1000;
        }

        if ($parts[1] == 'minute' || $parts[1] == 'minutes') {
            $factor = 60 * 1000;
        }

        if ($parts[1] == 'hour' || $parts[1] == 'hours') {
            $factor = 60 * 60 * 1000;
        }

        if ($parts[1] == 'day' || $parts[1] == 'days') {
            $factor = 24 * 60 * 60 * 1000;
        }

        if ($parts[1] == 'week' || $parts[1] == 'weeks') {
            $factor = 7 * 24 * 60 * 60 * 1000;
        }

        if (!$factor) {
            throw new LogicException($text);
        }

        return $parts[0] * $factor;
    }
}