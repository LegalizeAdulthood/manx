<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class AdminPageBaseTester extends Manx\AdminPageBase
{
    public function __construct($config)
    {
        $this->redirectCalled = false;
        parent::__construct($config);
    }

    public function param($name, $defaultValue = '')
    {
        return parent::param($name, $defaultValue);
    }

    protected function postPage()
    {
        throw new BadMethodCallException();
    }

    protected function renderBodyContent()
    {
        throw new BadMethodCallException();
    }

    protected function redirect($url)
    {
        $this->redirectCalled = true;
        $this->redirectLastUrl = $url;
    }
    public $redirectCalled, $redirectLastUrl;
}

class AdminPageBaseTest extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;
    /** @var Manx\IUser */
    private $_user;

    protected function setUp(): void
    {
        $manx = $this->createMock(Manx\IManx::class);
        $db = $this->createMock(Manx\IManxDatabase::class);
        $this->_user = $this->createMock(Manx\IUser::class);
        $manx->expects($this->once())->method('getDatabase')->willReturn($db);
        $manx->expects($this->once())->method('getUserFromSession')->willReturn($this->_user);

        $this->_config = new Container();
        $this->_config['db'] = $db;
        $this->_config['manx'] = $manx;
        unset($_SERVER['QUERY_STRING']);
    }

    public function testParamUrlWithoutPlusGivesUrl()
    {
        $url = 'http://foo';
        $this->_config['vars'] = ['url' => rawurlencode($url)];

        $page = new AdminPageBaseTester($this->_config);

        $this->assertEquals($url, $page->param('url'));
    }

    public function testParamUrlWithPlusGivesUrl()
    {
        $url = 'http://foo/3+Open';
        $this->_config['vars'] = ['url' => rawurlencode($url)];

        $page = new AdminPageBaseTester($this->_config);

        $this->assertEquals($url, $page->param('url'));
    }

    public function testLoginRedirectFromLoginPage()
    {
        $this->_user->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->_config['vars'] = [];
        $host = 'test.manx-docs.org';
        $redirect = 'https://' . $host . '/search.php';
        $url = 'https://' . $host . '/login.php?redirect=' . urlencode($redirect);
        $_SERVER['SERVER_NAME'] = $host;
        $_SERVER['PHP_SELF'] = $url;
        $_SERVER['SCRIPT_NAME'] = 'pages/login.php';
        $page = new AdminPageBaseTester($this->_config);

        $page->renderPage();

        $this->assertTrue($page->redirectCalled);
        $this->assertEquals($url, $page->redirectLastUrl);
    }

    public function testLoginRedirectPreservesQueryString()
    {
        $this->_user->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->_config['vars'] = [];
        $host = 'test.manx-docs.org';
        $_SERVER['SERVER_NAME'] = $host;
        $_SERVER['PHP_SELF'] = '/manx/whatsnew.php';
        $_SERVER['QUERY_STRING'] = 'site=bitsavers&parentDir=123';
        $_SERVER['SCRIPT_NAME'] = '/manx/whatsnew.php';
        $page = new AdminPageBaseTester($this->_config);

        $page->renderPage();

        $target = '/manx/whatsnew.php?site=bitsavers&parentDir=123';
        $expected = 'https://' . $host . '/manx/login.php?redirect=' . urlencode($target);
        $this->assertTrue($page->redirectCalled);
        $this->assertEquals($expected, $page->redirectLastUrl);
    }

    public function testLoginRedirectWithoutQueryString()
    {
        $this->_user->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->_config['vars'] = [];
        $host = 'test.manx-docs.org';
        $_SERVER['SERVER_NAME'] = $host;
        $_SERVER['PHP_SELF'] = '/manx/about.php';
        $_SERVER['SCRIPT_NAME'] = '/manx/about.php';
        $page = new AdminPageBaseTester($this->_config);

        $page->renderPage();

        $expected = 'https://' . $host . '/manx/login.php?redirect=%2Fmanx%2Fabout.php';
        $this->assertTrue($page->redirectCalled);
        $this->assertEquals($expected, $page->redirectLastUrl);
    }
}
