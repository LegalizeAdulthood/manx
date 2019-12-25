<?php

require_once 'vendor/autoload.php';

$config = Manx\Cron\CleanupConfig::getConfig();
$config['whatsNewCleaner'] = function($c)
{
    return new Manx\Cron\ChiClassicCompCleaner($c);
};
$processor = new WhatsNewProcessor($config);

if (count($argv) > 1)
{
    $processor->process($argv);
}
