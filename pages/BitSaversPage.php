<?php

require_once 'WhatsNewPageBase.php';

class BitSaversPage extends WhatsNewPageBase
{
    public function __construct($manx, $vars, IWhatsNewPageFactory $factory = null)
    {
        $opts = array(
            'indexByDateFile' => 'bitsavers-IndexByDate.txt',
            'indexByDateUrl' => 'http://bitsavers.trailing-edge.com/pdf/IndexByDate.txt',
            'timeStampProperty' => 'bitsavers_whats_new_timestamp',
            'urlBase' => 'http://bitsavers.trailing-edge.com/pdf',
            'siteName' => 'bitsavers',
            'menuType' => MenuType::BitSavers,
            'page' => 'bitsavers.php',
            'title' => 'BitSavers'
        );
        parent::__construct($manx, $vars, $opts, $factory);
    }
}
