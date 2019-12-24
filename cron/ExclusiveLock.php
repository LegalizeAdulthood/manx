<?php

namespace Manx\Cron;

require_once 'vendor/autoload.php';

// For PRIVATE_DIR
require_once 'pages/Config.php';

class ExclusiveLock implements IExclusiveLock
{
    public function lock($name)
    {
        return flock(fopen(PRIVATE_DIR . "/" . $name, "w"), LOCK_EX);
    }
}
