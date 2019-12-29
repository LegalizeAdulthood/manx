<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/DatabaseTester.php';

use Pimple\Container;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

class TestApiSiteUnknownDirs extends PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $db = $this->createMock(Manx\IManxDatabase::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $config = new Container();
        $config['db'] = $db;
        $api = new Manx\Api\SiteUnknownDirs($config);
        $siteName = 'bitsavers';
        $parentDirId = -1;
        $args = ['siteName' => $siteName, 'parentDirId' => $parentDirId];
        $rows = DatabaseTester::createResultRowsForColumns(['id', 'path'],
            [
                [1, 'foo/bar'],
                [2, 'foo']
            ]);
        $db->expects($this->once())->method('getSiteUnknownDirectories')
            ->with($siteName, $parentDirId)->willReturn($rows);
        $newResponse = $this->createMock(Response::class);
        $response->expects($this->once())->method('withJson')->with($rows, 200, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)->willReturn($newResponse);

        $result = $api->get($request, $response, $args);

        $this->assertEquals($newResponse, $result);
    }
}
