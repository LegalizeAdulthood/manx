<?php

require_once 'BitSaversConfig.php';
require_once 'WhatsNewPageBase.php';

use Pimple\Container;

class BitSaversPage extends WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        BitSaversConfig::configure($config);
        parent::__construct($config);
    }
}
