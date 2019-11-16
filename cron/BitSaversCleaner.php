<?php

require_once 'cron/WhatsNewCleaner.php';
require_once 'pages/BitSaversConfig.php';
require_once 'pages/WhatsNewIndex.php';

use Pimple\Container;

class BitSaversCleaner extends WhatsNewCleaner
{
    public function __construct(Container $config)
    {
        BitSaversConfig::configure($config);
        parent::__construct($config);
    }
}
