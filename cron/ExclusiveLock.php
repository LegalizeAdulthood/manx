<?php

require_once 'cron/IExclusiveLock.php';
require_once 'pages/Config.php';

class ExclusiveLock implements IExclusiveLock
{
    public function lock($name)
    {
        return flock(fopen(PRIVATE_DIR . "/" . $name, "w"), LOCK_EX);
    }
}
