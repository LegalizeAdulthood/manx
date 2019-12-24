<?php

namespace Manx;

require_once 'vendor/autoload.php';

class DateTimeProvider implements IDateTimeProvider
{
    public function now()
    {
        return new \DateTime("now", new \DateTimeZone('UTC'));
    }
}
