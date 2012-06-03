<?php

require_once 'Manx.php';
require_once 'PublicationPage.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new PublicationPage($manx, $vars);
$page->renderPage();

?>
