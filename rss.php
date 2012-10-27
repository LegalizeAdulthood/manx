<?php

require_once 'pages/Manx.php';
require_once 'pages/RssPage.php';
require_once 'pages/DateTimeProvider.php';

$manx = Manx::getInstance();
$dtp = new DateTimeProvider();
$page = new RssPage($manx, $dtp);
$page->renderPage();

?>
