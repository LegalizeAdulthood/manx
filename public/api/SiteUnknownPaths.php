<?php

namespace Manx\Api;

require __DIR__ . '/../../vendor/autoload.php';

use \Pimple\Container;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

class SiteUnknownPaths implements IApiObject
{
    public function __construct(Container $config)
    {
        $this->_db = $config['db'];
        $this->_user = $config['manx']->getUserFromSession();
    }

    public function get(Request $request, Response $response, array $args)
    {
        $paths = [];
        if ($this->_user->isLoggedIn())
        {
            $paths = $this->_db->getSiteUnknownPaths($args['siteName'], $args['parentDirId']);
        }
        return $response->withJson($paths, 200, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /** @var \Manx\IManxDatabase */
    private $_db;
    /** @var \Manx\IUser */
    private $_user;
};
