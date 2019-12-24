<?php

require_once 'vendor/autoload.php';

use Pimple\Container;

class ChiClassicCompPage extends Manx\WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        Manx\ChiClassicCompConfig::configure($config);
        parent::__construct($config);
    }
}
