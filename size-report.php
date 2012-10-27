<?php

require_once 'pages/Manx.php';
require_once 'pages/SizeReportPage.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new SizeReportPage($manx, $vars);
$page->renderPage();

?>
