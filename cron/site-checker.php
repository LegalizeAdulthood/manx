<?php

require_once 'vendor/autoload.php';

require_once 'cron/Logger.php';
require_once 'cron/SiteChecker.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx\Manx::getInstance();
$config['logger'] = new Logger();
$config['urlInfoFactory'] = new Manx\UrlInfoFactory();

$checker = new SiteChecker($config);
$checker->checkSites();
