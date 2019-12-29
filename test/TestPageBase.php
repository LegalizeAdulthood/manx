<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class PageBaseTester extends Manx\PageBase
{
    protected function renderBodyContent()
    {
    }

    public function renderAuthorization()
    {
        parent::renderAuthorization();
    }

    public function renderMenu()
    {
        parent::renderMenu();
    }
}

class TestPageBase extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->_manx = $this->createMock(Manx\IManx::class);
        $this->_user = $this->createMock(Manx\IUser::class);
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
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

        $this->_page->renderLoginLink(['PHP_SELF' => '/manx/about.php',
            'SCRIPT_NAME' => '/manx/about.php',
            'SERVER_NAME' => 'localhost']);

        $this->expectOutputString('<a href="https://localhost/manx/login.php?redirect=%2Fmanx%2Fabout.php">Login</a>');
    }

    public function testRenderLoginLinkFromLoginPage()
    {
        $this->createInstance();

        $this->_page->renderLoginLink(['PHP_SELF' => '/manx/login.php?redirect=%2Fmanx%2Fabout.php',
            'SCRIPT_NAME' => '/manx/login.php',
            'SERVER_NAME' => 'localhost']);

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

    public function testRenderMenu()
    {
        $this->_user->expects($this->once())->method('isAdmin')->willReturn(false);
        $this->_manx->expects($this->once())->method('getUserFromSession')->willReturn($this->_user);
        $this->createInstance();

        $this->_page->renderMenu();

        $output = <<<EOH
<div class="menu"><a class="first" href="search.php">Search</a><a href="news.php">News</a><a href="about.php">About</a><a href="help.php">Help</a><a href="rss.php"><img style="vertical-align: middle" src="assets/rss.png"></a></div>

EOH;
        $this->expectOutputString($output);
    }

    public function testRenderAdminMenu()
    {
        $this->_user->expects($this->once())->method('isAdmin')->willReturn(true);
        $this->_manx->expects($this->once())->method('getUserFromSession')->willReturn($this->_user);
        $this->createInstance();

        $this->_page->renderMenu();

        $output = <<<EOH
<div class="menu"><a class="first" href="search.php">Search</a><a href="news.php">News</a><a href="about.php">About</a><a href="help.php">Help</a><a href="rss.php"><img style="vertical-align: middle" src="assets/rss.png"></a></div>
<div class="menu">
<a class="first" href="url-wizard.php">URL Wizard</a><a href="whatsnew.php?site=bitsavers&parentDir=-1">BitSavers</a><a href="whatsnew.php?site=chiclassiccomp&parentDir=-1">ChiClassicComp</a><a href="size-report.php">Size Report</a><a href="md5-report.php">MD5 Report</a></div>
<div class="menu">
<a class="first" href="site.php">Site</a><a href="mirror.php">Mirror</a></div>

EOH;
        $this->expectOutputString($output);
    }

    /** @var Manx\IManx */
    private $_manx;
    /** @var Manx\IUser */
    private $_user;
    /** @var Manx\IManxDatabase */
    private $_db;
    /** @var PageBaseTester */
    private $_page;
}
