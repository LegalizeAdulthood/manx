<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

class TestApiSiteUnknownDirs extends PHPUnit\Framework\TestCase
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
        $this->_api = new Manx\Api\SiteUnknownDirs($this->_config);
    }

    public function testGetRequiresLogin()
    {
        $this->_user->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $siteName = 'bitsavers';
        $parentDirId = -1;
        $args = ['siteName' => $siteName, 'parentDirId' => $parentDirId];
        $rows = [];
        $this->_db->expects($this->never())->method('getSiteUnknownDirectories');
        $this->_response->expects($this->once())->method('withJson')
            ->with($rows, 200, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            ->willReturn($this->_newResponse);

        $result = $this->_api->get($this->_request, $this->_response, $args);

        $this->assertEquals($this->_newResponse, $result);
    }

    public function testGet()
    {
        $this->_user->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $siteName = 'bitsavers';
        $parentDirId = -1;
        $args = ['siteName' => $siteName, 'parentDirId' => $parentDirId];
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'path'],
            [
                [1, 'foo/bar'],
                [2, 'foo']
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')
            ->with($siteName, $parentDirId)->willReturn($rows);
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
    /** @var Manx\Api\SiteUnknownDirs */
    private $_api;
}
