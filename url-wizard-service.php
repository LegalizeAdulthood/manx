<?php

require_once 'Manx.php';
require_once 'Searcher.php';
require_once 'UrlWizardService.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new UrlWizardService($manx, $vars);
$page->processRequest();

?>
