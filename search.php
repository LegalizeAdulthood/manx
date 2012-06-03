<?php

require_once 'Manx.php';
require_once 'SearchPage.php';

$manx = Manx::getInstance();
$page = new SearchPage($manx);
$page->renderPage();

?>
