<?php

require_once 'vendor/autoload.php';

require_once 'pages/Manx.php';
require_once 'pages/WhatsNewPageFactory.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$config['fileSystem'] = new Manx\FileSystem();
$config['whatsNewPageFactory'] = new WhatsNewPageFactory();
$page = new Manx\BitSaversPage($config);
$page->renderPage();
