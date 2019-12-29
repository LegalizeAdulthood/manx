<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/DatabaseTester.php';

use Pimple\Container;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

class TestApiSiteUnknownPaths extends PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $db = $this->createMock(Manx\IManxDatabase::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $config = new Container();
        $config['db'] = $db;
        $api = new Manx\Api\SiteUnknownPaths($config);
        $siteName = 'bitsavers';
        $parentDirId = -1;
        $args = ['siteName' => $siteName, 'parentDirId' => $parentDirId];
        $rows = DatabaseTester::createResultRowsForColumns(['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [1, 3, 'foo.pdf', 0, 0, -1],
                [1, 3, 'foo.jpg', 1, 0, -1]
            ]);
        $db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)->willReturn($rows);
        $newResponse = $this->createMock(Response::class);
        $response->expects($this->once())->method('withJson')->with($rows, 200, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)->willReturn($newResponse);

        $result = $api->get($request, $response, $args);

        $this->assertEquals($newResponse, $result);
    }
}
