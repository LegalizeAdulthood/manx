<?php

require_once 'vendor/autoload.php';

class DateTimeProvider implements Manx\IDateTimeProvider
{
    public function now()
    {
        return new DateTime("now", new DateTimeZone('UTC'));
    }
}
