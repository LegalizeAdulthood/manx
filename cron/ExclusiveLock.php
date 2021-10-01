<?php

namespace Manx\Cron;

require_once __DIR__ . '/../vendor/autoload.php';

// For PRIVATE_DIR
require_once __DIR__ . '/../public/pages/Config.php';

class ExclusiveLock implements IExclusiveLock
{
    public function lock($name)
    {
        return flock(fopen(PRIVATE_DIR . "/" . $name, "w"), LOCK_EX);
    }
}
