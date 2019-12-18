<?php

require_once 'cron/BitSaversCleaner.php';
require_once 'pages/IFile.php';
require_once 'pages/IWhatsNewIndex.php';

use Pimple\Container;

class TestBitSaversCleaner extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;

    /** @var IManxDatabase */
    private $_db;
    /** @var IManx */
    private $_manx;
    /** @var IUrlInfo */
    private $_urlInfo;
    /** @var IWhatsNewPageFactory */
    private $_factory;
    /** @var ILogger */
    private $_logger;
    /** @var BitSaversCleaner */
    private $_cleaner;
    /** @var IWhatsNewIndex */
    private $_whatsNewIndex;

    protected function setUp()
    {
        $this->_urlInfo = $this->createMock(IUrlInfo::class);
        $this->_factory = $this->createMock(IWhatsNewPageFactory::class);
        $this->_logger = $this->createMock(ILogger::class);

        $this->_db = $this->createMock(IManxDatabase::class);
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->expects($this->atLeast(1))->method('getDatabase')->willReturn($this->_db);
        $this->_whatsNewIndex = $this->createMock(IWhatsNewIndex::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['logger'] = $this->_logger;
        $config['whatsNewPageFactory'] = $this->_factory;
        $config['whatsNewIndex'] = $this->_whatsNewIndex;
        $config['fileSystem'] = $this->createMock(IFileSystem::class);
        $this->_config = $config;
        $this->_cleaner = new BitSaversCleaner($this->_config);
    }

    public function testNonExistentPathsAreRemoved()
    {
        $this->_db->expects($this->once())->method('getAllSiteUnknownPaths')
            ->with('bitsavers')
            ->willReturn( array( array('id' => 1, 'path' => 'foo/path.pdf') ) );
        $this->_db->expects($this->once())->method('removeSiteUnknownPathById')
            ->with('bitsavers', 1);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(false);
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with('http://bitsavers.trailing-edge.com/pdf/foo/path.pdf')
            ->willReturn($this->_urlInfo);

        $this->_cleaner->removeNonExistentUnknownPaths();
    }

    public function testExistingPathsAreKept()
    {
        $this->_db->expects($this->once())->method('getAllSiteUnknownPaths')
            ->willReturn(array(
                array('id' => 1, 'path' => 'foo/path.pdf')
            ));
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(true);
        $this->_factory->expects($this->once())->method('createUrlInfo')->willReturn($this->_urlInfo);

        $this->_cleaner->removeNonExistentUnknownPaths();
    }

    public function testPathsEscapeSpecialChars()
    {
        $this->_db->expects($this->once())->method('getAllSiteUnknownPaths')
            ->willReturn(array(
                array('id' => 1, 'path' => 'foo/path#1.pdf')
            ));
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(true);
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with('http://bitsavers.trailing-edge.com/pdf/foo/path%231.pdf')
            ->willReturn($this->_urlInfo);

        $this->_cleaner->removeNonExistentUnknownPaths();
    }

    public function testMovedFilesAreUpdated()
    {
        $md5 = '37e10bd2e8da6bd96eb3a72feeea56ee';
        $this->_db->expects($this->once())->method('getPossiblyMovedSiteUnknownPaths')
            ->with('bitsavers')
            ->willReturn( array(
                array('path' => 'hp/newDir/foo.pdf', 'path_id' => 16,
                    'url' => 'http://bitsavers.org/pdf/hp/foo.pdf', 'copy_id' => 10, 'md5' => $md5)
            ));
        $this->_db->expects($this->once())->method('siteFileMoved')
            ->with('bitsavers', 10, 16, 'http://bitsavers.org/pdf/hp/newDir/foo.pdf');
        $this->_urlInfo->expects($this->once())->method('md5')->willReturn($md5);
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with('http://bitsavers.trailing-edge.com/pdf/hp/newDir/foo.pdf')
            ->willReturn($this->_urlInfo);

        $this->_cleaner->updateMovedFiles();
    }

    public function testUpdateWhatsNewNotNewer()
    {
        $this->_whatsNewIndex->expects($this->once())->method('needIndexByDateFile')->willReturn(false);
        $this->_whatsNewIndex->expects($this->never())->method('getIndexByDateFile');
        $this->_whatsNewIndex->expects($this->never())->method('parseIndexByDateFile');

        $this->_cleaner->updateWhatsNewIndex();
    }

    public function testUpdateWhatsNew()
    {
        $this->_whatsNewIndex->expects($this->once())->method('needIndexByDateFile')->willReturn(true);
        $this->_whatsNewIndex->expects($this->once())->method('getIndexByDateFile');
        $this->_whatsNewIndex->expects($this->once())->method('parseIndexByDateFile');
        $this->_logger->expects($this->once())->method('log');

        $this->_cleaner->updateWhatsNewIndex();
    }
}
