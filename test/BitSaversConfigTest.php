<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class TestBitSaversConfig extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->_config = new Container();
        Manx\BitSaversConfig::configure($this->_config);
    }

    public function testSiteName()
    {
        $this->assertEquals('bitsavers', $this->_config['siteName']);
    }

    public function testTimeStampProperty()
    {
        $this->assertEquals('bitsavers_whats_new_timestamp', $this->_config['timeStampProperty']);
    }

    public function testIndexByDateFile()
    {
        $this->assertEquals('bitsavers-IndexByDate.txt', $this->_config['indexByDateFile']);
    }

    public function testIndexByDateUrl()
    {
        $this->assertEquals('http://bitsavers.trailing-edge.com/pdf/IndexByDate.txt', $this->_config['indexByDateUrl']);
    }

    public function testBaseCheckUrl()
    {
        $this->assertEquals('http://bitsavers.trailing-edge.com/pdf', $this->_config['baseCheckUrl']);
    }

    public function testBaseUrl()
    {
        $this->assertEquals('http://bitsavers.org/pdf', $this->_config['baseUrl']);
    }

    public function testMenuType()
    {
        $this->assertEquals(Manx\MenuType::BitSavers, $this->_config['menuType']);
    }

    public function testPage()
    {
        $this->assertEquals('whatsnew.php?site=bitsavers', $this->_config['page']);
    }

    public function testTitle()
    {
        $this->assertEquals('BitSavers', $this->_config['title']);
    }

    private $_config;
}
