<?php

require_once 'cron/BitSaversCleaner.php';
require_once 'pages/Manx.php';
require_once 'pages/WhatsNewPageFactory.php';

$manx = Manx::getInstance();
$factory = new WhatsNewPageFactory();
$cleaner = new BitSaversCleaner($manx, $factory);

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
