<?php

require_once 'cron/ILogger.php';

class Logger implements ILogger
{
    function log($line)
    {
        print($line . "\n");
    }
}
