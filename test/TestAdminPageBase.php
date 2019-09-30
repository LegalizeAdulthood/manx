<?php

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
    public function testParamUrlWithoutPlusGivesUrl()
    {
        $manx = $this->createMock(IManx::class);
        $manx->expects($this->once())->method('getDatabase')->willReturn($this->createMock(IManxDatabase::class));
        $url = 'http://foo';

        $page = new AdminPageBaseTester($manx, array('url' => rawurlencode($url)));

        $this->assertEquals($url, $page->param('url'));
    }

    public function testParamUrlWithPlusGivesUrl()
    {
        $manx = $this->createMock(IManx::class);
        $manx->expects($this->once())->method('getDatabase')->willReturn($this->createMock(IManxDatabase::class));
        $url = 'http://foo/3+Open';

        $page = new AdminPageBaseTester($manx, array('url' => rawurlencode($url)));

        $this->assertEquals($url, $page->param('url'));
    }
}
