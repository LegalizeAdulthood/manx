<?php

require_once 'pages/Manx.php';
require_once 'pages/DetailsPage.php';

$manx = Manx::getInstance();
$page = new Detailspage($manx);
$page->renderPage();

?>
