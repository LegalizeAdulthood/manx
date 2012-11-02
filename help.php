<?php

require_once 'pages/HelpPage.php';
require_once 'pages/Manx.php';

$manx = Manx::getInstance();
$page = new HelpPage($manx);
$page->renderPage();
