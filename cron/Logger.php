<?php

namespace Manx\Cron;

require_once __DIR__ . '/../vendor/autoload.php';

class Logger implements ILogger
{
    public function __construct(\Manx\IDateTimeProvider $dateTimeProvider = null)
    {
        $this->_dateTimeProvider = is_null($dateTimeProvider)
            ? new \Manx\DateTimeProvider()
            : $dateTimeProvider;
    }

    function log($line)
    {
        printf("[%s] %s\n",
            $this->_dateTimeProvider->now()->format('Y-m-d H:i:s'),
            $line);
    }

    /** @var \Manx\IDateTimeProvider */
    private $_dateTimeProvider;
}
