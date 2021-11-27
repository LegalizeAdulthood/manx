<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

$pathInfo = $_SERVER['PATH_INFO'];

$config = new Container();
$config['manx'] = Manx\Manx::getInstance();
$config['vars'] = ($_SERVER['REQUEST_METHOD'] == 'POST') ? $_POST : $_GET;
$config['fileSystem'] = new Manx\FileSystem();
$config['whatsNewPageFactory'] = new Manx\WhatsNewPageFactory();

$siteName = $config['vars']['site'];
if ($siteName === 'bitsavers')
{
    Manx\BitSaversConfig::configure($config);
}
else if ($siteName === 'VTDA')
{
    Manx\VtdaConfig::configure($config);
}
else
{
    throw new InvalidArgumentException("Unknown site name '" . $siteName . "'");
}
$page = new Manx\WhatsNewPage($config);
$page->renderPage();
