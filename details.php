<?php

require_once 'vendor/autoload.php';
require_once 'pages/Manx.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$page = new Manx\DetailsPage($config);
$page->renderPage();
