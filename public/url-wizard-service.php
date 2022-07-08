<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

$config = new Container();
$manx = Manx\Manx::getInstance();
$config['manx'] = $manx;
$config['db'] = $manx->getDatabase();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$config['urlInfoFactory'] = new Manx\UrlInfoFactory();
$config['urlMetaData'] = function($c)
{
    return new Manx\UrlMetaData($c);
};
$page = new Manx\UrlWizardService($config);
$page->processRequest();
