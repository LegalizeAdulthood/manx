<?php

require_once 'vendor/autoload.php';
require_once 'pages/NewsPage.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx\Manx::getInstance();
$page = new NewsPage($config);
$page->renderPage();
