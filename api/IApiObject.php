<?php

namespace Manx\Api;

use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

interface IApiObject
{
    public function get(Request $request, Response $response, array $args);
}
