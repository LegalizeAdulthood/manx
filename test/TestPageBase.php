<?php

require_once 'vendor/autoload.php';
require_once 'pages/IManx.php';
require_once 'pages/IManxDatabase.php';
require_once 'pages/IUser.php';
require_once 'pages/PageBase.php';

use Pimple\Container;

class PageBaseTester extends PageBase
{
    protected function renderBodyContent()
    {
    }

    public function renderAuthorization()
    {
        parent::renderAuthorization();
    }
}

class TestPageBase extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->_manx = $this->createMock(IManx::class);
        $this->_user = $this->createMock(IUser::class);
        $this->_db = $this->createMock(IManxDatabase::class);
    }

    private function createInstance()
    {
        $_SERVER['PATH_INFO'] = '';
        $config = new Container();
        $config['manx'] = $this->_manx;
        $this->_page = new PageBaseTester($config);
    }

    public function testRenderLoginLink()
    {
        $this->createInstance();

        $this->_page->renderLoginLink(array('PHP_SELF' => '/manx/about.php',
            'SCRIPT_NAME' => '/manx/about.php',
            'SERVER_NAME' => 'localhost'));

        $this->expectOutputString('<a href="https://localhost/manx/login.php?redirect=%2Fmanx%2Fabout.php">Login</a>');
    }

    public function testRenderAuthorization()
    {
        $_SERVER['SERVER_NAME'] = 'manx-docs.org';
        $_SERVER['PHP_SELF'] = 'about.php';
        $this->_db->expects($this->once())->method('getManxVersion')->willReturn('2.0.7');
        $this->_manx->expects($this->once())->method('getDatabase')->willReturn($this->_db);
        $this->_manx->expects($this->once())->method('getUserFromSession')->willReturn($this->_user);
        $this->_user->expects($this->once())->method('displayName')->willReturn('Guest');
        $this->_user->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->createInstance();

        $this->_page->renderAuthorization();

        $output = <<<EOH
<div id="AUTH"><table>
<tr><td>Guest | <a href="https://manx-docs.org/bin/login.php?redirect=about.php">Login</a></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td class="version" align="right">version 2.0.7</td></tr>
</table></div>

EOH;
        $this->expectOutputString($output);
    }

    /** @var IManx */
    private $_manx;
    /** @var IUser */
    private $_user;
    /** @var IManxDatabase */
    private $_db;
    /** @var PageBaseTester */
    private $_page;
}
