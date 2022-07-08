<?php

namespace Manx\Cron;

require_once __DIR__ . '/../vendor/autoload.php';

class ExclusiveLock implements IExclusiveLock
{
    public function lock($name)
    {
        return flock(fopen(\Manx\Config::configFile($name), "w"), LOCK_EX);
    }
}
