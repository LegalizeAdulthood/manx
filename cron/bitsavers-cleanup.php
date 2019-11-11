<?php

require_once 'cron/cleanup-deps.php';
require_once 'cron/BitSaversCleaner.php';

$config = CleanupConfig::getConfig();
$config['whatsNewCleaner'] = function($c)
{
    return new BitSaversCleaner($c);
};
$processor = new WhatsNewProcessor($config);

if (count($argv) > 1)
{
    $processor->process($argv);
}
