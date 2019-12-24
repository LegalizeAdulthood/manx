<?php

namespace Manx;

require_once 'vendor/autoload.php';

use Pimple\Container;

class BitSaversConfig
{
    static public function configure(Container $config)
    {
        $config['siteName'] = 'bitsavers';
        $config['timeStampProperty'] = 'bitsavers_whats_new_timestamp';
        $config['indexByDateFile'] = 'bitsavers-IndexByDate.txt';
        $config['indexByDateUrl'] = 'http://bitsavers.trailing-edge.com/pdf/IndexByDate.txt';
        $config['baseCheckUrl'] = 'http://bitsavers.trailing-edge.com/pdf';
        $config['baseUrl'] = 'http://bitsavers.org/pdf';
        $config['menuType'] = MenuType::BitSavers;
        $config['page'] = 'bitsavers.php';
        $config['title'] = 'BitSavers';
    }
}

