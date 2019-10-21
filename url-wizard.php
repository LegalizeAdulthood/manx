<?php

require_once 'vendor/autoload.php';
require_once 'pages/UrlWizardPage.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx::getInstance();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new URLWizardPage($config);
$page->renderPage();
