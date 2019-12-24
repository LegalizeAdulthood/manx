<?php

require_once 'vendor/autoload.php';

use Pimple\Container;

class BitSaversPage extends Manx\WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        Manx\BitSaversConfig::configure($config);
        parent::__construct($config);
    }
}
