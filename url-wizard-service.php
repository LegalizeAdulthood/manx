<?php

require_once 'vendor/autoload.php';

require_once 'pages/UrlMetaData.php';
require_once 'pages/UrlWizardService.php';

use Pimple\Container;

$config = new Container();
$manx = Manx\Manx::getInstance();
$config['manx'] = $manx;
$config['db'] = $manx->getDatabase();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$config['urlInfoFactory'] = new Manx\UrlInfoFactory();
$config['urlMetaData'] = function($c)
{
    return new UrlMetaData($c);
};
$page = new UrlWizardService($config);
$page->processRequest();
