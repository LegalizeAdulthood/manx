<?php

require_once 'vendor/autoload.php';

require_once 'cron/BitSaversCleaner.php';

$config = Manx\Cron\CleanupConfig::getConfig();
$config['whatsNewCleaner'] = function($c)
{
    return new BitSaversCleaner($c);
};
$processor = new WhatsNewProcessor($config);

if (count($argv) > 1)
{
    $processor->process($argv);
}
