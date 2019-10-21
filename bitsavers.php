<?php

require_once 'vendor/autoload.php';
require_once 'pages/BitSaversPage.php';
require_once 'pages/File.php';
require_once 'pages/Manx.php';
require_once 'pages/WhatsNewPageFactory.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$config['fileSystem'] = new FileSystem();
$config['whatsNewPageFactory'] = new WhatsNewPageFactory();
$page = new BitSaversPage($config);
$page->renderPage();
