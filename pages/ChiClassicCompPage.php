<?php

require_once 'vendor/autoload.php';

require_once 'WhatsNewPageBase.php';

use Pimple\Container;

class ChiClassicCompPage extends WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        Manx\ChiClassicCompConfig::configure($config);
        parent::__construct($config);
    }
}
