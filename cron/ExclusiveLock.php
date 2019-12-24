<?php

require_once 'vendor/autoload.php';

require_once 'pages/Config.php';

class ExclusiveLock implements Manx\Cron\IExclusiveLock
{
    public function lock($name)
    {
        return flock(fopen(PRIVATE_DIR . "/" . $name, "w"), LOCK_EX);
    }
}
