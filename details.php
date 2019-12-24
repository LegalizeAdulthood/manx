<?php

require_once 'vendor/autoload.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx\Manx::getInstance();
$page = new Manx\DetailsPage($config);
$page->renderPage();
