<?php

require_once 'cron/BitSaversCleaner.php';
require_once 'test/FakeWhatsNewPageFactory.php';
require_once 'test/FakeManx.php';
require_once 'test/FakeManxDatabase.php';
require_once 'test/FakeUrlInfo.php';

class FakeLogger implements ILogger
{
    function log($line)
    {
    }
}

class TestBitSaversCleaner extends PHPUnit\Framework\TestCase
{
    /** @var FakeManxDatabase */
    private $_db;
    /** @var FakeManx */
    private $_manx;
    /** @var FakeWhatsNewPageFactory */
    private $_factory;
    /** @var Logger */
    private $_logger;
    /** @var BitSaversCleaner */
    private $_cleaner;

    protected function setUp()
    {
        $this->_db = new FakeManxDatabase();
        $this->_manx = new FakeManx();
        $this->_manx->getDatabaseFakeResult = $this->_db;
        $this->_factory = new FakeWhatsNewPageFactory();
        $this->_logger = new FakeLogger();
        $this->_cleaner = new BitSaversCleaner($this->_manx, $this->_factory, $this->_logger);
    }

    public function testNonExistentPathsAreRemoved()
    {
        $this->_db->getAllSiteUnknownPathsResult = array(
            array('id' => 1, 'path' => 'foo/path.pdf')
        );
        $urlInfo = new FakeUrlInfo();
        $urlInfo->existsFakeResult = false;
        $this->_factory->createUrlInfoFakeResult = $urlInfo;

        $this->_cleaner->removeNonExistentUnknownPaths();

        $this->assertTrue($this->_db->getAllSiteUnknownPathsCalled);
        $this->assertEquals('bitsavers', $this->_db->getAllSiteUnknownPathsLastSiteName);
        $this->assertEquals('http://bitsavers.trailing-edge.com/pdf/foo/path.pdf', $this->_factory->createUrlInfoLastUrl);
        $this->assertTrue($urlInfo->existsCalled);
        $this->assertTrue($this->_db->removeSiteUnknownPathByIdCalled);
        $this->assertEquals('bitsavers', $this->_db->removeSiteUnknownPathByIdLastSiteName);
        $this->assertEquals(1, $this->_db->removeSiteUnknownPathByIdLastId);
    }

    public function testExistingPathsAreKept()
    {
        $this->_db->getAllSiteUnknownPathsResult = array(
            array('id' => 1, 'path' => 'foo/path.pdf')
        );
        $urlInfo = new FakeUrlInfo();
        $urlInfo->existsFakeResult = true;
        $this->_factory->createUrlInfoFakeResult = $urlInfo;

        $this->_cleaner->removeNonExistentUnknownPaths();

        $this->assertFalse($this->_db->removeSiteUnknownPathByIdCalled);
    }

    public function testPathsEscapeSpecialChars()
    {
        $this->_db->getAllSiteUnknownPathsResult = array(
            array('id' => 1, 'path' => 'foo/path#1.pdf')
        );
        $urlInfo = new FakeUrlInfo();
        $urlInfo->existsFakeResult = true;
        $this->_factory->createUrlInfoFakeResult = $urlInfo;

        $this->_cleaner->removeNonExistentUnknownPaths();

        $this->assertEquals('http://bitsavers.trailing-edge.com/pdf/foo/path%231.pdf', $this->_factory->createUrlInfoLastUrl);
    }

    public function testMovedFilesAreUpdated()
    {
        $md5 = '37e10bd2e8da6bd96eb3a72feeea56ee';
        $this->_db->getPossiblyMovedSiteUnknownPathsFakeResult = array(
            array('path' => 'hp/newDir/foo.pdf', 'path_id' => 16,
                'url' => 'http://bitsavers.org/pdf/hp/foo.pdf', 'copy_id' => 10, 'md5' => $md5)
        );
        $urlInfo = new FakeUrlInfo();
        $urlInfo->md5FakeResult = $md5;
        $this->_factory->createUrlInfoFakeResult = $urlInfo;

        $this->_cleaner->updateMovedFiles();

        $this->assertTrue($this->_db->getPossiblyMovedSiteUnknownPathsCalled);
        $this->assertEquals('bitsavers', $this->_db->getPossiblyMovedSiteUnknownPathsLastSiteName);
        $this->assertTrue($this->_factory->createUrlInfoCalled);
        $this->assertEquals('http://bitsavers.trailing-edge.com/pdf/hp/newDir/foo.pdf', $this->_factory->createUrlInfoLastUrl);
        $this->assertTrue($urlInfo->md5Called);
        $this->assertTrue($this->_db->siteFileMovedCalled);
        $this->assertEquals('bitsavers', $this->_db->siteFileMovedLastSiteName);
        $this->assertEquals(10, $this->_db->siteFileMovedLastCopyId);
        $this->assertEquals(16, $this->_db->siteFileMovedLastPathId);
        $this->assertEquals('http://bitsavers.org/pdf/hp/newDir/foo.pdf', $this->_db->siteFileMovedLastUrl);
    }
}
