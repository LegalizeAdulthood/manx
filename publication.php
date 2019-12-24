<?php

require_once 'vendor/autoload.php';
require_once 'pages/PublicationPage.php';

use Pimple\Container;

$config = new Container();
$config['manx'] = Manx\Manx::getInstance();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$page = new PublicationPage($config);
$page->renderPage();
