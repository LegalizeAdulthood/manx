<?php

require_once 'vendor/autoload.php';
require_once 'pages/Manx.php';
require_once 'pages/RssPage.php';
require_once 'pages/DateTimeProvider.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$config['dateTimeProvider'] = new DateTimeProvider();
$page = new RssPage($config);
$page->renderPage();
