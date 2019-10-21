<?php

require_once 'vendor/autoload.php';
require_once 'pages/Manx.php';
require_once 'pages/MD5ReportPage.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new MD5ReportPage($config);
$page->renderPage();
