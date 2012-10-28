<?php

require_once 'pages/Manx.php';
require_once 'pages/BitSaversPage.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new BitSaversPage($manx, $vars);
$page->renderPage();

?>
