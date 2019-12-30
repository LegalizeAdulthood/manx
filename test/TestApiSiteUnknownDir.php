<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/DatabaseTester.php';

use Pimple\Container;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

class TestApiSiteUnknownDir extends PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $db = $this->createMock(Manx\IManxDatabase::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $config = new Container();
        $config['db'] = $db;
        $api = new Manx\Api\SiteUnknownDir($config);
        $dirId = 33;
        $args = ['dirId' => $dirId];
        $rows = DatabaseTester::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex'],
            [
                [33, 3, 'foo/bar', 44, ''],
            ]);
        $db->expects($this->once())->method('getSiteUnknownDir')->with($dirId)->willReturn($rows);
        $newResponse = $this->createMock(Response::class);
        $response->expects($this->once())->method('withJson')->with($rows, 200, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)->willReturn($newResponse);

        $result = $api->get($request, $response, $args);

        $this->assertEquals($newResponse, $result);
    }
}
