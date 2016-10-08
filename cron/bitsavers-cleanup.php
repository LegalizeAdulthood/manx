<?php

require_once '../pages/Manx.php';
require_once 'BitSaversCleaner.php';

$manx = Manx::getInstance();
$cleaner = new BitSaversCleaner($manx);
$cleaner->removeNonExistentUnknownPaths();
