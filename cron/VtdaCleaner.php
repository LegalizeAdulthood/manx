<?php

namespace Manx\Cron;

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class VtdaCleaner extends WhatsNewCleaner
{
    public function __construct($config)
    {
        \Manx\VtdaConfig::configure($config);
        parent::__construct($config);
    }
}
