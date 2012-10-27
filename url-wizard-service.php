<?php

require_once 'pages/Manx.php';
require_once 'pages/Searcher.php';
require_once 'pages/UrlWizardService.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new UrlWizardService($manx, $vars);
$page->processRequest();

?>
