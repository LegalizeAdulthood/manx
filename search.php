<?php

require_once 'vendor/autoload.php';
require_once 'pages/SearchPage.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx\Manx::getInstance();
$page = new SearchPage($config);
$page->renderPage();
