<?php

require_once 'vendor/autoload.php';
require_once 'pages/Manx.php';
require_once 'pages/Searcher.php';
require_once 'pages/UrlInfoFactory.php';
require_once 'pages/UrlMetaData.php';
require_once 'pages/UrlWizardService.php';

use Pimple\Container;

$config = new Container();
$manx = Manx::getInstance();
$config['manx'] = $manx;
$config['db'] = $manx->getManxDatabase();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$config['urlInfoFactory'] = new UrlInfoFactory();
$config['urlMetaData'] = function($c)
{
    return new UrlMetaData($c);
};
$page = new UrlWizardService($config);
$page->processRequest();
