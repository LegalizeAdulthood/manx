<?php

require_once 'vendor/autoload.php';
require_once 'cron/BitSaversCleaner.php';
require_once 'cron/Logger.php';
require_once 'cron/WhatsNewProcessor.php';
require_once 'pages/File.php';
require_once 'pages/IngestionRobotUser.php';
require_once 'pages/Manx.php';
require_once 'pages/UrlInfoFactory.php';
require_once 'pages/UrlMetaData.php';
require_once 'pages/WhatsNewIndex.php';
require_once 'pages/WhatsNewPageFactory.php';

use Pimple\Container;

$manx = Manx::getInstance();
$config = new Container();
$config['manx'] = $manx;
$config['db'] = $manx->getDatabase();
$config['whatsNewPageFactory'] = new WhatsNewPageFactory();
$config['logger'] = new Logger();
$config['fileSystem'] = new FileSystem();
$config['user'] = function($c)
{
    return new IngestionRobotUser($c);
};
$config['whatsNewIndex'] = function($c)
{
    return new WhatsNewIndex($c);
};
$config['whatsNewCleaner'] = function($c)
{
    return new BitSaversCleaner($c);
};
$config['urlMetaData'] = function($c)
{
    return new UrlMetaData($c);
};
$config['urlInfoFactory'] = new UrlInfoFactory();
$processor = new WhatsNewProcessor($config);

if (count($argv) > 1)
{
    $processor->process($argv);
}
