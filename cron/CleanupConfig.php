<?php

namespace Manx\Cron;

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class CleanupConfig
{
    public static function getConfig()
    {
        $config = new Container();
        $manx = \Manx\Manx::getInstance();
        $config['manx'] = $manx;
        $config['db'] = $manx->getDatabase();
        $config['whatsNewPageFactory'] = new \Manx\WhatsNewPageFactory();
        $config['locker'] = new ExclusiveLock();
        $config['logger'] = new Logger();
        $config['fileSystem'] = new \Manx\FileSystem();
        $config['user'] = function($c)
        {
            return new \Manx\IngestionRobotUser($c);
        };
        $config['whatsNewIndex'] = function($c)
        {
            return new \Manx\WhatsNewIndex($c);
        };
        $config['urlMetaData'] = function($c)
        {
            return new \Manx\UrlMetaData($c);
        };
        $config['urlInfoFactory'] = new \Manx\UrlInfoFactory();
        return $config;
    }
}
