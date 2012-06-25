<?php

require_once 'Manx.php';
require_once 'RssPage.php';
require_once 'DateTimeProvider.php';

$manx = Manx::getInstance();
$dtp = new DateTimeProvider();
$page = new RssPage($manx, $dtp);
$page->renderPage();

?>
