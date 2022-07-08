<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx\Manx::getInstance();
$config['dateTimeProvider'] = new Manx\DateTimeProvider();
$page = new Manx\RssPage($config);
$page->renderPage();
