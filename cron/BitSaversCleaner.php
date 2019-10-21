<?php

require_once 'cron/WhatsNewCleaner.php';
require_once 'pages/WhatsNewIndex.php';

use Pimple\Container;

class BitSaversCleaner extends WhatsNewCleaner
{
    public function __construct(Container $config)
    {
        $config['siteName'] = 'bitsavers';
        $config['timeStampProperty'] = 'bitsavers_whats_new_timestamp';
        $config['indexByDateFile'] = 'bitsavers-IndexByDate.txt';
        $config['indexByDateUrl'] = 'http://bitsavers.trailing-edge.com/pdf/IndexByDate.txt';
        $config['baseCheckUrl'] = 'http://bitsavers.trailing-edge.com/pdf/';
        $config['baseUrl'] = 'http://bitsavers.org/pdf/';
        parent::__construct($config);
    }
}
