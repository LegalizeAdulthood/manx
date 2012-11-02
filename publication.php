<?php

require_once 'pages/Manx.php';
require_once 'pages/PublicationPage.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new PublicationPage($manx, $vars);
$page->renderPage();
