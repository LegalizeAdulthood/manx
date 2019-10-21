<?php

require_once 'WhatsNewPageBase.php';

use Pimple\Container;

define('TIMESTAMP_PROPERTY', 'bitsavers_whats_new_timestamp');
define('INDEX_BY_DATE_FILE', 'bitsavers-IndexByDate.txt');
define('INDEX_BY_DATE_URL', 'http://bitsavers.trailing-edge.com/pdf/IndexByDate.txt');

class BitSaversPage extends WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        $config['opts'] = array(
            'indexByDateFile' => INDEX_BY_DATE_FILE,
            'indexByDateUrl' => INDEX_BY_DATE_URL,
            'timeStampProperty' => TIMESTAMP_PROPERTY,
            'urlBase' => 'http://bitsavers.trailing-edge.com/pdf',
            'siteName' => 'bitsavers',
            'menuType' => MenuType::BitSavers,
            'page' => 'bitsavers.php',
            'title' => 'BitSavers'
        );
        parent::__construct($config);
    }
}
