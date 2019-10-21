<?php

require_once 'vendor/autoload.php';

use Pimple\Container;

require_once 'pages/AdminPageBase.php';

class AdminPageBaseTester extends AdminPageBase
{
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
}

class TestAdminPageBase extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;

    protected function setUp()
    {
        $manx = $this->createMock(IManx::class);
        $db = $this->createMock(IManxDatabase::class);
        $manx->expects($this->once())->method('getDatabase')->willReturn($db);

        $this->_config = new Container();
        $this->_config['db'] = $db;
        $this->_config['manx'] = $manx;
    }

    public function testParamUrlWithoutPlusGivesUrl()
    {
        $url = 'http://foo';
        $this->_config['vars'] = array('url' => rawurlencode($url));

        $page = new AdminPageBaseTester($this->_config);

        $this->assertEquals($url, $page->param('url'));
    }

    public function testParamUrlWithPlusGivesUrl()
    {
        $url = 'http://foo/3+Open';
        $this->_config['vars'] = array('url' => rawurlencode($url));

        $page = new AdminPageBaseTester($this->_config);

        $this->assertEquals($url, $page->param('url'));
    }
}
