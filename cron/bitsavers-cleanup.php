<?php

require_once 'vendor/autoload.php';
require_once 'cron/BitSaversCleaner.php';
require_once 'cron/Logger.php';
require_once 'cron/WhatsNewProcessor.php';
require_once 'pages/File.php';
require_once 'pages/Manx.php';
require_once 'pages/WhatsNewIndex.php';
require_once 'pages/WhatsNewPageFactory.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$config['whatsNewPageFactory'] = new WhatsNewPageFactory();
$config['logger'] = new Logger();
$config['fileSystem'] = new FileSystem();
$config['whatsNewIndex'] = function($c)
{
    return new WhatsNewIndex($c);
};
$config['whatsNewCleaner'] = function($c)
{
    return new BitSaversCleaner($c);
};
$processor = new WhatsNewProcessor($config);

if (count($argv) > 1)
{
    $processor->process($argv);
}
