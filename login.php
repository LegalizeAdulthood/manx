<?php

require_once 'pages/Manx.php';
require_once 'pages/LoginPage.php';

$manx = Manx::getInstance();
$page = new LoginPage($manx);
$page->renderPage();
