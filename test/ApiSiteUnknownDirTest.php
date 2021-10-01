<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/DatabaseTester.php';

use Pimple\Container;
use Psr\Http\Message\UriInterface;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

class TestApiSiteUnknownDir extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->_config = new Container();
        $manx = $this->createMock(Manx\IManx::class);
        $this->_user = $this->createMock(Manx\IUser::class);
        $manx->expects($this->once())->method('getUserFromSession')->willReturn($this->_user);
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_config['manx'] = $manx;
        $this->_config['db'] = $this->_db;
        $this->_request = $this->createMock(Request::class);
        $this->_response = $this->createMock(Response::class);
        $this->_newResponse = $this->createMock(Response::class);
        $this->_api = new Manx\Api\SiteUnknownDir($this->_config);
    }

    public function testGetRequiresLogin()
    {
        $this->_user->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $dirId = 128;
        $args = ['dirId' => $dirId];
        $this->_db->expects($this->never())->method('getSiteUnknownDir');
        $this->_response->expects($this->once())->method('withJson')
            ->with((object) [], 200, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ->willReturn($this->_newResponse);

        $result = $this->_api->get($this->_request, $this->_response, $args);

        $this->assertEquals($this->_newResponse, $result);
    }

    public function testGet()
    {
        $this->_user->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $dirId = 33;
        $args = ['dirId' => $dirId];
        $rows = DatabaseTester::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex'],
            [
                [33, 3, 'foo/bar', 44, ''],
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')->with($dirId)->willReturn($rows);
        $this->_response->expects($this->once())->method('withJson')
            ->with($rows, 200, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ->willReturn($this->_newResponse);

        $result = $this->_api->get($this->_request, $this->_response, $args);

        $this->assertEquals($this->_newResponse, $result);
    }

    /** @var Container */
    private $_config;
    /** @var Manx\IManx */
    private $_manx;
    /** @var Manx\IManxDatabase */
    private $_db;
    /** @var Manx\IUser */
    private $_user;
    /** @var Request */
    private $_request;
    /** @var Response */
    private $_response;
    /** @var Response */
    private $_newResponse;
    /** @var Manx\Api\SiteUnknownDir */
    private $_api;
}
