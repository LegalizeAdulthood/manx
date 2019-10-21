<?php

require_once 'vendor/autoload.php';
require_once 'pages/Manx.php';
require_once 'pages/LoginPage.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$page = new LoginPage($config);
$page->renderPage();
