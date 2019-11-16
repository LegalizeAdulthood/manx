<?php

require_once 'ChiClassicCompConfig.php';
require_once 'WhatsNewPageBase.php';

use Pimple\Container;

class ChiClassicCompPage extends WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        ChiClassicCompConfig::configure($config);
        parent::__construct($config);
    }
}
