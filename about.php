<?php

require_once 'Manx.php';
require_once 'AboutPage.php';

$manx = Manx::getInstance();
$page = new AboutPage($manx);
$page->renderPage();

?>
