<?php

require_once 'cron/BitSaversCleaner.php';
require_once 'test/FakeBitSaversPageFactory.php';
require_once 'test/FakeManx.php';
require_once 'test/FakeManxDatabase.php';
require_once 'test/FakeUrlInfo.php';

class TestBitSaversCleaner extends PHPUnit_Framework_TestCase
{
    /** @var FakeManxDatabase */
    private $_db;
    /** @var FakeManx */
    private $_manx;
    /** @var FakeBitSaversPageFactory */
    private $_factory;
    /** @var BitSaversCleaner */
    private $_cleaner;

    protected function setUp()
    {
        $this->_db = new FakeManxDatabase();
        $this->_manx = new FakeManx();
        $this->_manx->getDatabaseFakeResult = $this->_db;
        $this->_factory = new FakeBitSaversPageFactory();
        $this->_cleaner = new BitSaversCleaner($this->_manx, $this->_factory);
    }

    public function testNonExistentPathsAreRemoved()
    {
        $this->_db->getAllBitSaversUnknownPathsResult = array(
            array('id' => 1, 'path' => 'foo/path.pdf')
        );
        $urlInfo = new FakeUrlInfo();
        $urlInfo->httpStatusFakeResult = 404;
        $this->_factory->createUrlInfoFakeResult = $urlInfo;

        $this->_cleaner->removeNonExistentUnknownPaths();

        $this->assertTrue($this->_db->getAllBitSaversUnknownPathsCalled);
        $this->assertEquals('http://bitsavers.org/pdf/foo/path.pdf', $this->_factory->createUrlInfoLastUrl);
        $this->assertTrue($urlInfo->httpStatusCalled);
        $this->assertTrue($this->_db->removeBitSaversUnknownPathByIdCalled);
        $this->assertEquals(1, $this->_db->removeBitSaversUnknownPathByIdLastId);
    }

    public function testExistingPathsAreKept()
    {
        $this->_db->getAllBitSaversUnknownPathsResult = array(
            array('id' => 1, 'path' => 'foo/path.pdf')
        );
        $urlInfo = new FakeUrlInfo();
        $urlInfo->httpStatusFakeResult = 200;
        $this->_factory->createUrlInfoFakeResult = $urlInfo;

        $this->_cleaner->removeNonExistentUnknownPaths();

        $this->assertFalse($this->_db->removeBitSaversUnknownPathByIdCalled);
    }
}
