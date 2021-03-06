<?php

namespace Manx\Cron;

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class ChiClassicCompCleaner extends WhatsNewCleaner
{
    public function __construct($config)
    {
        \Manx\ChiClassicCompConfig::configure($config);
        parent::__construct($config);
    }
}
