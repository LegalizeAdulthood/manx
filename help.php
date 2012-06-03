<?php

require_once 'HelpPage.php';
require_once 'Manx.php';

$manx = Manx::getInstance();
$page = new HelpPage($manx);
$page->renderPage();

?>
