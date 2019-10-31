<?php

require_once 'pages/IngestionRobotUser.php';

use Pimple\Container;

class TestIngestionRobotUser extends PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $manx = $this->createMock(IManx::class);
        $db = $this->createMock(IManxDatabase::class);
        $userId = 3;
        $db->expects($this->once())->method('getIngestionRobotUser')->willReturn($userId);
        $manx->method('getDatabase')->willReturn($db);
        $config = new Container();
        $config['manx'] = $manx;

        $user = new IngestionRobotUser($config);

        $this->assertFalse(is_null($user));
        $this->assertTrue(is_object($user));
    }
}
