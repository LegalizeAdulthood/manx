<?php

require_once 'vendor/autoload.php';
require_once 'pages/NewsPage.php';
require_once 'pages/Manx.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$page = new NewsPage($config);
$page->renderPage();
