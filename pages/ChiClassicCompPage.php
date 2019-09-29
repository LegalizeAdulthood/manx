<?php

require_once 'WhatsNewPageBase.php';

define('CCC_TIMESTAMP_PROPERTY', 'chiclassiccomp_whats_new_timestamp');
define('CCC_INDEX_BY_DATE_FILE', 'chiClassicComp-IndexByDate.txt');
define('CCC_INDEX_BY_DATE_URL', 'http://chiclassiccomp.org/docs/content/IndexByDate.txt');

class ChiClassicCompPage extends WhatsNewPageBase
{
    public function __construct($manx, $vars, IFileSystem $fileSystem = null, IWhatsNewPageFactory $factory = null)
    {
        $opts = array(
            'indexByDateFile' => CCC_INDEX_BY_DATE_FILE,
            'indexByDateUrl' => CCC_INDEX_BY_DATE_URL,
            'timeStampProperty' => CCC_TIMESTAMP_PROPERTY,
            'urlBase' => 'http://chiclassiccomp.org/docs/content',
            'siteName' => 'ChiClassicComp',
            'menuType' => MenuType::ChiClassicComp,
            'page' => 'chiclassiccomp.php',
            'title' => 'ChiClassicComp'
        );
        parent::__construct($manx, $vars, $opts, $fileSystem, $factory);
    }
}
