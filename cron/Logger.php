<?php

require_once 'vendor/autoload.php';

class Logger implements Manx\Cron\ILogger
{
    function log($line)
    {
        print($line . "\n");
    }
}
