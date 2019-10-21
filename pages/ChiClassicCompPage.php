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
        $config['opts'] = array(
            'indexByDateFile' => CCC_INDEX_BY_DATE_FILE,
            'indexByDateUrl' => CCC_INDEX_BY_DATE_URL,
            'timeStampProperty' => CCC_TIMESTAMP_PROPERTY,
            'urlBase' => 'http://chiclassiccomp.org/docs/content',
            'siteName' => 'ChiClassicComp',
            'menuType' => MenuType::ChiClassicComp,
            'page' => 'chiclassiccomp.php',
            'title' => 'ChiClassicComp'
        );
        parent::__construct($config);
    }
}
