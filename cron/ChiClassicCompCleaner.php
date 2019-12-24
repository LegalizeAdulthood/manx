<?php

require_once 'vendor/autoload.php';

require_once 'cron/WhatsNewCleaner.php';
require_once 'pages/WhatsNewIndex.php';

use Pimple\Container;

class ChiClassicCompCleaner extends WhatsNewCleaner
{
    public function __construct($config)
    {
        Manx\ChiClassicCompConfig::configure($config);
        parent::__construct($config);
    }
}
