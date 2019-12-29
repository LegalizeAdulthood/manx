<?php

namespace Manx\Cron;

require_once __DIR__ . '/../vendor/autoload.php';

class Logger implements ILogger
{
    function log($line)
    {
        print($line . "\n");
    }
}
