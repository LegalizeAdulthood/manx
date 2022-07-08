<?php

require __DIR__ . '/../vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = true;

$app = new \Slim\App(['settings' => $config]);

$container = $app->getContainer();
$container['manx'] = function($config) {
    return \Manx\Manx::getInstance();
};
$container['db'] = function($config) {
    return $config['manx']->getDatabase();
};

$container['siteUnknownDir'] = function($config) {
    return new \Manx\Api\SiteUnknownDir($config);
};
$app->get('/siteUnknownDir/{dirId}', function(Request $request, Response $response, array $args) {
    return $this->siteUnknownDir->get($request, $response, $args);
});

$container['siteUnknownDirs'] = function($config) {
    return new \Manx\Api\SiteUnknownDirs($config);
};
$app->get('/siteUnknownDirs/{siteName}/{parentDirId}', function(Request $request, Response $response, array $args) {
    return $this->siteUnknownDirs->get($request, $response, $args);
});

$container['siteUnknownPaths'] = function($config) {
    return new \Manx\Api\SiteUnknownPaths($config);
};
$app->get('/siteUnknownPaths/{siteName}/{parentDirId}', function(Request $request, Response $response, array $args) {
    return $this->siteUnknownPaths->get($request, $response, $args);
});

$app->run();
