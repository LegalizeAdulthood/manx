<?php

require_once 'Manx.php';
require_once 'DetailsPage.php';

$manx = Manx::getInstance();
$page = new Detailspage($manx);
$page->renderPage();

?>
