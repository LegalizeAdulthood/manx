<?php

require_once 'vendor/autoload.php';

require_once 'cron/ChiClassicCompCleaner.php';

$config = Manx\Cron\CleanupConfig::getConfig();
$config['whatsNewCleaner'] = function($c)
{
    return new ChiClassicCompCleaner($c);
};
$processor = new WhatsNewProcessor($config);

if (count($argv) > 1)
{
    $processor->process($argv);
}
