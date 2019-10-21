<?php

require_once 'vendor/autoload.php';
require_once 'pages/HelpPage.php';
require_once 'pages/Manx.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$page = new HelpPage($config);
$page->renderPage();
