<?php

require_once 'cron/WhatsNewCleaner.php';

use Pimple\Container;

class BitSaversCleaner extends WhatsNewCleaner
{
    public function __construct(Container $config)
    {
        $config['siteName'] = 'bitsavers';
        $config['baseCheckUrl'] = 'http://bitsavers.trailing-edge.com/pdf/';
        $config['baseUrl'] = 'http://bitsavers.org/pdf/';
        parent::__construct($config);
    }
}
