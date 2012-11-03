<?php

require_once 'pages/NewsPage.php';
require_once 'pages/Manx.php';

$manx = Manx::getInstance();
$page = new NewsPage($manx);
$page->renderPage();
