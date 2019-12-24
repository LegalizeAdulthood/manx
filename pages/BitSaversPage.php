<?php

namespace Manx;

require_once 'vendor/autoload.php';

use Pimple\Container;

class BitSaversPage extends WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        BitSaversConfig::configure($config);
        parent::__construct($config);
    }
}
