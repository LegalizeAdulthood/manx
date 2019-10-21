<?php

require_once 'vendor/autoload.php';
require_once 'cron/BitSaversCleaner.php';
require_once 'pages/Manx.php';
require_once 'pages/WhatsNewPageFactory.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$config['whatsNewPageFactory'] = new WhatsNewPageFactory();
$config['logger'] = new Logger();
$cleaner = new BitSaversCleaner($config);

if (count($argv) > 1)
{
    if ($argv[1] == 'existence')
    {
        $cleaner->removeNonExistentUnknownPaths();
    }
    else if ($argv[1] == 'moved')
    {
        $cleaner->updateMovedFiles();
    }
}
