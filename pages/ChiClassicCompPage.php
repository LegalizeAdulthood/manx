<?php

require_once 'WhatsNewPageBase.php';

use Pimple\Container;

define('CCC_TIMESTAMP_PROPERTY', 'chiclassiccomp_whats_new_timestamp');
define('CCC_INDEX_BY_DATE_FILE', 'chiClassicComp-IndexByDate.txt');
define('CCC_INDEX_BY_DATE_URL', 'http://chiclassiccomp.org/docs/content/IndexByDate.txt');

class ChiClassicCompPage extends WhatsNewPageBase
{
    public function __construct(Container $config)
    {
        $config['indexByDateFile'] = CCC_INDEX_BY_DATE_FILE;
        $config['indexByDateUrl'] = CCC_INDEX_BY_DATE_URL;
        $config['timeStampProperty'] = CCC_TIMESTAMP_PROPERTY;
        $config['baseUrl'] = 'http://chiclassiccomp.org/docs/content';
        $config['siteName'] = 'ChiClassicComp';
        $config['menuType'] = MenuType::ChiClassicComp;
        $config['page'] = 'chiclassiccomp.php';
        $config['title'] = 'ChiClassicComp';
        parent::__construct($config);
    }
}
