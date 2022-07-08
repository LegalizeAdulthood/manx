<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class VtdaConfigTest extends PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        $this->_config = new Container();
        Manx\VtdaConfig::configure($this->_config);
    }

    public function testSiteName()
    {
        $this->assertEquals('VTDA', $this->_config['siteName']);
    }

    public function testTimeStampProperty()
    {
        $this->assertEquals('vtda_whats_new_timestamp', $this->_config['timeStampProperty']);
    }

    public function testIndexByDateFile()
    {
        $this->assertEquals('vtda-IndexByDate.txt', $this->_config['indexByDateFile']);
    }

    public function testIndexByDateUrl()
    {
        $this->assertEquals('http://vtda.org/docs/IndexByDate.txt', $this->_config['indexByDateUrl']);
    }

    public function testBaseCheckUrl()
    {
        $this->assertEquals('http://vtda.org/docs', $this->_config['baseCheckUrl']);
    }

    public function testBaseUrl()
    {
        $this->assertEquals('http://vtda.org/docs', $this->_config['baseUrl']);
    }

    public function testMenuType()
    {
        $this->assertEquals(Manx\MenuType::Vtda, $this->_config['menuType']);
    }

    public function testPage()
    {
        $this->assertEquals('whatsnew.php?site=VTDA', $this->_config['page']);
    }

    public function testTitle()
    {
        $this->assertEquals('VTDA', $this->_config['title']);
    }

    private $_config;
}
