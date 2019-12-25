<?php

namespace Manx\Cron;

require_once 'vendor/autoload.php';

use Pimple\Container;

class BitSaversCleaner extends WhatsNewCleaner
{
    public function __construct(Container $config)
    {
        \Manx\BitSaversConfig::configure($config);
        parent::__construct($config);
    }
}
