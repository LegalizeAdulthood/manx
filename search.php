<?php

require_once 'vendor/autoload.php';
require_once 'pages/Manx.php';
require_once 'pages/SearchPage.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$page = new SearchPage($config);
$page->renderPage();
