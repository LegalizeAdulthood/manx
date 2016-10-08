<?php

require_once 'cron/BitSaversCleaner.php';
require_once 'pages/Manx.php';
require_once 'pages/BitSaversPageFactory.php';

$manx = Manx::getInstance();
$factory = new BitSaversPageFactory();
$cleaner = new BitSaversCleaner($manx, $factory);

$cleaner->removeNonExistentUnknownPaths();
