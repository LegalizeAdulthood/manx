<?php

require_once 'pages/CompanyPage.php';

$manx = Manx::getInstance();
$vars = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new Companypage($manx, $vars);
$page->renderPage();
