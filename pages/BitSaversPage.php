<?php

require_once 'vendor/autoload.php';

require_once 'WhatsNewPageBase.php';

use Pimple\Container;

class BitSaversPage extends WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        Manx\BitSaversConfig::configure($config);
        parent::__construct($config);
    }
}
