<?php

require_once 'pages/Manx.php';
require_once 'pages/ChiClassicCmpPage.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new ChiClassicCmpPage($manx, $vars);
$page->renderPage();
