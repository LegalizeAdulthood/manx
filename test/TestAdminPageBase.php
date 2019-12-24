<?php

require_once 'vendor/autoload.php';

use Pimple\Container;

require_once 'pages/AdminPageBase.php';

class AdminPageBaseTester extends AdminPageBase
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

class TestAdminPageBase extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;
    /** @var Manx\IUser */
    private $_user;

    protected function setUp()
    {
        $manx = $this->createMock(Manx\IManx::class);
        $db = $this->createMock(IManxDatabase::class);
        $this->_user = $this->createMock(Manx\IUser::class);
        $manx->expects($this->once())->method('getDatabase')->willReturn($db);
        $manx->expects($this->once())->method('getUserFromSession')->willReturn($this->_user);

        $this->_config = new Container();
        $this->_config['db'] = $db;
        $this->_config['manx'] = $manx;
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
}
