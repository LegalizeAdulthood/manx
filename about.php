<?php

require_once 'pages/Manx.php';
require_once 'pages/AboutPage.php';

$manx = Manx::getInstance();
$page = new AboutPage($manx);
$page->renderPage();
