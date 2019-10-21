<?php

require_once 'vendor/autoload.php';
require_once 'pages/Manx.php';
require_once 'pages/AboutPage.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$page = new AboutPage($config);
$page->renderPage();
