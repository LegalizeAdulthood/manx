<?php

require_once 'vendor/autoload.php';
require_once 'pages/Manx.php';
require_once 'pages/Searcher.php';
require_once 'pages/UrlInfoFactory.php';
require_once 'pages/UrlWizardService.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$config['urlInfoFactory'] = new UrlInfoFactory();
$page = new UrlWizardService($config);
$page->processRequest();
