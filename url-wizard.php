<?php

require_once __DIR__ . '/vendor/autoload.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx\Manx::getInstance();
$config['db'] = function($c) {
    return $c['manx']->getDatabase();
};
$config['urlInfoFactory'] = new Manx\UrlInfoFactory();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$config['urlMetaData'] = function($c)
{
    return new Manx\UrlMetaData($c);
};
$page = new Manx\UrlWizardPage($config);
$page->renderPage();
