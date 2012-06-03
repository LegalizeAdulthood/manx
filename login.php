<?php

require_once 'Manx.php';
require_once 'LoginPage.php';

$manx = Manx::getInstance();
$page = new LoginPage($manx);
$page->renderPage();

?>
