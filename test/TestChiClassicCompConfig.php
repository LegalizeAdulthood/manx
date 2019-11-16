<?php

require_once 'pages/ChiClassicCompConfig.php';

use Pimple\Container;

class TestChiClassicCompConfig extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->_config = new Container();
        ChiClassicCompConfig::configure($this->_config);
    }

    public function testSiteName()
    {
        $this->assertEquals('ChiClassicComp', $this->_config['siteName']);
    }

    public function testTimeStampProperty()
    {
        $this->assertEquals('chiclassiccomp_whats_new_timestamp', $this->_config['timeStampProperty']);
    }

    public function testIndexByDateFile()
    {
        $this->assertEquals('chiClassicComp-IndexByDate.txt', $this->_config['indexByDateFile']);
    }

    public function testIndexByDateUrl()
    {
        $this->assertEquals('http://chiclassiccomp.org/docs/content/IndexByDate.txt', $this->_config['indexByDateUrl']);
    }

    public function testBaseCheckUrl()
    {
        $this->assertEquals('http://chiclassiccomp.com/docs/content/', $this->_config['baseCheckUrl']);
    }

    public function testBaseUrl()
    {
        $this->assertEquals('http://chiclassiccomp.com/docs/content/', $this->_config['baseUrl']);
    }

    public function testMenuType()
    {
        $this->assertEquals(MenuType::ChiClassicComp, $this->_config['menuType']);
    }

    public function testPage()
    {
        $this->assertEquals('chiclassiccomp.php', $this->_config['page']);
    }

    public function testTitle()
    {
        $this->assertEquals('ChiClassicComp', $this->_config['title']);
    }

    private $_config;
}
