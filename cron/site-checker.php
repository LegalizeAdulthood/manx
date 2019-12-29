<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx\Manx::getInstance();
$config['logger'] = new Manx\Cron\Logger();
$config['urlInfoFactory'] = new Manx\UrlInfoFactory();

$checker = new Manx\Cron\SiteChecker($config);
$checker->checkSites();
