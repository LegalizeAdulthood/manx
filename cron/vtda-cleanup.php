<?php

require_once __DIR__ . '/../vendor/autoload.php';

$config = Manx\Cron\CleanupConfig::getConfig();
$config['whatsNewCleaner'] = function($c)
{
    return new Manx\Cron\VtdaCleaner($c);
};
$processor = new Manx\Cron\WhatsNewProcessor($config);

if (count($argv) > 1)
{
    $processor->process($argv);
}
