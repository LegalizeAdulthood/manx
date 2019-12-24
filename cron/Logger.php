<?php

namespace Manx\Cron;

require_once 'vendor/autoload.php';

class Logger implements ILogger
{
    function log($line)
    {
        print($line . "\n");
    }
}
