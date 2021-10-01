<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

class DateTimeProvider implements IDateTimeProvider
{
    public function now()
    {
        return new \DateTime("now", new \DateTimeZone('UTC'));
    }
}
