<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/pages/IDateTimeProvider.php';

class UserTest extends PHPUnit\Framework\TestCase
{
    private $_oldCookie;

    protected function setUp(): void
    {
        $this->_oldCookie = $_COOKIE;
        $_COOKIE[Manx\Cookie::NAME] = '20260820120000.123456';
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->_oldCookie;
    }

    public function testMissingSessionReturnsGuest()
    {
        $db = $this->createMock(Manx\IManxDatabase::class);
        $db->expects($this->once())->method('getUserFromSessionId')
            ->with($_COOKIE[Manx\Cookie::NAME])
            ->willReturn(array());
        $db->expects($this->never())->method('deleteUserSession');

        $user = Manx\User::getInstanceFromSession($db);

        $this->assertFalse($user->isLoggedIn());
        $this->assertFalse($user->isAdmin());
        $this->assertEquals(-1, $user->userId());
    }

    public function testCurrentSessionReturnsUser()
    {
        $db = $this->createMock(Manx\IManxDatabase::class);
        $db->expects($this->once())->method('getUserFromSessionId')
            ->with($_COOKIE[Manx\Cookie::NAME])
            ->willReturn($this->userRow(time() - 60));
        $db->expects($this->never())->method('deleteUserSession');

        $user = Manx\User::getInstanceFromSession($db);

        $this->assertTrue($user->isLoggedIn());
        $this->assertTrue($user->isAdmin());
        $this->assertEquals(66, $user->userId());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExpiredSessionReturnsGuest()
    {
        $sessionId = $_COOKIE[Manx\Cookie::NAME];
        $db = $this->createMock(Manx\IManxDatabase::class);
        $db->expects($this->once())->method('getUserFromSessionId')
            ->with($sessionId)
            ->willReturn($this->userRow(time() - 31*60));
        $db->expects($this->once())->method('deleteUserSession')
            ->with($sessionId);

        $user = Manx\User::getInstanceFromSession($db);

        $this->assertFalse($user->isLoggedIn());
        $this->assertFalse($user->isAdmin());
        $this->assertEquals(-1, $user->userId());
    }

    private function userRow($lastImpression)
    {
        date_default_timezone_set(Manx\TIME_ZONE);
        return [
            'user_id' => 66,
            'logged_in' => 1,
            'last_impression' => strftime('%Y-%m-%d %H:%M:%S', $lastImpression),
            'first_name' => 'Test',
            'last_name' => 'User'
        ];
    }
}
