<?php

require_once 'vendor/autoload.php';

use Pimple\Container;

class ChiClassicCompConfig
{
    static public function configure(Container $config)
    {
        $config['siteName'] = 'ChiClassicComp';
        $config['timeStampProperty'] = 'chiclassiccomp_whats_new_timestamp';
        $config['indexByDateFile'] = 'chiClassicComp-IndexByDate.txt';
        $config['indexByDateUrl'] = 'http://chiclassiccomp.org/docs/content/IndexByDate.txt';
        $config['baseCheckUrl'] = 'http://chiclassiccomp.org/docs/content';
        $config['baseUrl'] = 'http://chiclassiccomp.org/docs/content';
        $config['menuType'] = Manx\MenuType::ChiClassicComp;
        $config['page'] = 'chiclassiccomp.php';
        $config['title'] = 'ChiClassicComp';
    }
}

