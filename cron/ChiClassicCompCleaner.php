<?php

require_once 'cron/WhatsNewCleaner.php';
require_once 'pages/WhatsNewIndex.php';

use Pimple\Container;

class ChiClassicCompCleaner extends WhatsNewCleaner
{
    public function __construct($config)
    {
        $config['siteName'] = 'ChiClassicComp';
        $config['timeStampProperty'] = 'chiclassiccomp_whats_new_timestamp';
        $config['indexByDateFile'] = 'chiclassiccomp-IndexByDate.txt';
        $config['indexByDateUrl'] = 'http://chiclassiccomp.org/docs/content/IndexByDate.txt';
        $config['baseCheckUrl'] = 'http://chiclassiccomp.org/docs/content';
        $config['baseUrl'] = 'http://chiclassiccomp.org/docs/content';
        parent::__construct($config);
    }
}
