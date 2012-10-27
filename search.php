<?php

require_once 'pages/Manx.php';
require_once 'pages/SearchPage.php';

$manx = Manx::getInstance();
$page = new SearchPage($manx);
$page->renderPage();

?>
