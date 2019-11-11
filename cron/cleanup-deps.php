<?php

require_once 'vendor/autoload.php';

require_once 'cron/ExclusiveLock.php';
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

class CleanupConfig
{
    public static function getConfig()
    {
        $config = new Container();
        $manx = Manx::getInstance();
        $config['manx'] = $manx;
        $config['db'] = $manx->getDatabase();
        $config['whatsNewPageFactory'] = new WhatsNewPageFactory();
        $config['locker'] = new ExclusiveLock();
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
        $config['urlMetaData'] = function($c)
        {
            return new UrlMetaData($c);
        };
        $config['urlInfoFactory'] = new UrlInfoFactory();
        return $config;
    }
}