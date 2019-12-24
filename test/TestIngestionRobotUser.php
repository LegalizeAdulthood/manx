<?php

require_once 'vendor/autoload.php';

use Pimple\Container;

class TestIngestionRobotUser extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->_manx = $this->createMock(Manx\IManx::class);
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_userId = 3;
        $this->_db->expects($this->once())->method('getIngestionRobotUser')->willReturn($this->_userId);
        $this->_manx->method('getDatabase')->willReturn($this->_db);
        $config = new Container();
        $config['manx'] = $this->_manx;

        $this->_user = new Manx\IngestionRobotUser($config);
    }

    public function testConstruct()
    {

        $this->assertFalse(is_null($this->_user));
        $this->assertTrue(is_object($this->_user));
    }

    public function testUserId()
    {
        $this->assertEquals($this->_userId, $this->_user->userId());
    }

    public function testDisplayName()
    {
        $this->assertEquals('Ingestion Robot', $this->_user->displayName());
    }
}
