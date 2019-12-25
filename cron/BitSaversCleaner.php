<?php

require_once 'vendor/autoload.php';

require_once 'pages/WhatsNewIndex.php';

use Pimple\Container;

class BitSaversCleaner extends Manx\Cron\WhatsNewCleaner
{
    public function __construct(Container $config)
    {
        Manx\BitSaversConfig::configure($config);
        parent::__construct($config);
    }
}
