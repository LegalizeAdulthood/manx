<?php

require_once 'pages/Manx.php';
require_once 'pages/ChiClassicCompPage.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new ChiClassicCompPage($manx, $vars);
$page->renderPage();
