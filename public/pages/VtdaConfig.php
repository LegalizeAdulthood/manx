<?php

namespace Manx;

require_once __DIR__ . '/../../vendor/autoload.php';

use Pimple\Container;

class VtdaConfig
{
    static public function configure(Container $config)
    {
        $config['siteName'] = 'VTDA';
        $config['timeStampProperty'] = 'vtda_whats_new_timestamp';
        $config['indexByDateFile'] = 'vtda-IndexByDate.txt';
        $config['indexByDateUrl'] = 'http://vtda.org/docs/IndexByDate.txt';
        $config['baseCheckUrl'] = 'http://vtda.org/docs';
        $config['baseUrl'] = 'http://vtda.org/docs';
        $config['menuType'] = MenuType::Vtda;
        $config['page'] = 'whatsnew.php?site=VTDA';
        $config['title'] = 'VTDA';
    }
}

