<?php

require_once 'cron/WhatsNewCleaner.php';

use Pimple\Container;

class ChiClassicCompCleaner extends WhatsNewCleaner
{
    function __construct($config)
    {
        $config['siteName'] = 'ChiClassicComp';
        $config['baseUrl'] = 'http://chiclassiccomp.org/docs/content';
        $config['baseCheckUrl'] = $config['baseUrl'];
        parent::construct($config);
    }
}
