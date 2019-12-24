<?php

namespace Manx\Cron;

interface IExclusiveLock
{
    function lock($name);
}
