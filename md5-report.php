<?php

require_once 'pages/Manx.php';
require_once 'pages/MD5ReportPage.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new MD5ReportPage($manx, $vars);
$page->renderPage();

?>
