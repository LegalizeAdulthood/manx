<?php

namespace Manx;

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class ChiClassicCompPage extends WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        ChiClassicCompConfig::configure($config);
        parent::__construct($config);
    }
}
