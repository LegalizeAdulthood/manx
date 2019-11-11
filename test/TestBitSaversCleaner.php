<?php

require_once 'cron/BitSaversCleaner.php';
require_once 'pages/IFile.php';
require_once 'pages/IManx.php';
require_once 'pages/IManxDatabase.php';
require_once 'pages/IUser.php';
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
    /** @var IUser
    private $_user;
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
        $this->_urlMetaData = $this->createMock(IUrlMetaData::class);
        $this->_user = $this->createMock(IUser::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['logger'] = $this->_logger;
        $config['whatsNewPageFactory'] = $this->_factory;
        $config['whatsNewIndex'] = $this->_whatsNewIndex;
        $config['fileSystem'] = $this->createMock(IFileSystem::class);
        $config['urlMetaData'] = $this->_urlMetaData;
        $config['user'] = $this->_user;
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
        $this->_logger->expects($this->exactly(2))->method('log');

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
        $this->_logger->expects($this->once())->method('log');

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
            ->willReturn( [
                ['path' => 'hp/newDir/foo.pdf', 'path_id' => 16,
                    'url' => 'http://bitsavers.org/pdf/hp/foo.pdf', 'copy_id' => 10, 'md5' => $md5]
            ]);
        $this->_db->expects($this->once())->method('siteFileMoved')
            ->with('bitsavers', 10, 16, 'http://bitsavers.org/pdf/hp/newDir/foo.pdf');
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with('http://bitsavers.trailing-edge.com/pdf/hp/newDir/foo.pdf')
            ->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('md5')->willReturn($md5);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(true);

        $this->_cleaner->updateMovedFiles();
    }

    public function testRemoveUnknownPathsWithCopy()
    {
        $this->_db->expects($this->once())->method('removeUnknownPathsWithCopy');
        $this->_logger->expects($this->once())->method('log');

        $this->_cleaner->removeUnknownPathsWithCopy();
    }

    private function bitsaversMetaData($siteId, $companyId, $url)
    {
        return [
            'url' => $url,
            'mirror_url' => '',
            'size' => 5555,
            'valid' => true,
            'site' => [
                'site_id' => $siteId,
                'name' => 'bitsavers',
                'url' => 'http://bitsavers.org',
                'description' => '',
                'copy_base' => 'http://bitsavers.org/pdf/',
                'low' => 'N',
                'live' => 'Y',
                'display_order' => 1
            ],
            'company' => $companyId,
            'part' => 'EK-3333-01',
            'pub_date' => '1977-02',
            'title' => 'Jumbotron Users Guide',
            'format' => 'PDF',
            'site_company_directory' => 'dec',
            'pubs' => [],
        ];
    }

    private function stockPubData()
    {
        return [
            'pub_type' => 'D',
            'alt_part' => '',
            'revision' => '',
            'keywords' => '',
            'notes' => '',
            'abstract' => '',
            'languages' => ''
        ];
    }

    private function stockCopyData()
    {
        return [
            'notes' => '',
            'credits' => '',
            'amend_serial' => ''
        ];
    }

    public function testComputeMissingMD5CopyExists()
    {
        $url = 'http://bitsavers.org/pdf/dec/jtron/Jumbotron_Users_Manual.pdf';
        $copyId = 5544;
        $docs = DatabaseTester::createResultRowsForColumns([ 'copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url' ],
            [
                [ $copyId, 23, 100, 'Jumobotron Users Manual', $url ]
            ]);
        $this->_db->expects($this->once())->method('getAllMissingMD5Documents')->willReturn($docs);
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($url)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(true);
        $copyMD5 = 'deadbeeffacef00d';
        $this->_urlInfo->expects($this->once())->method('md5')->willReturn($copyMD5);
        $this->_db->expects($this->once())->method('updateMD5ForCopy')->with($copyId, $copyMD5);
        $this->_logger->expects($this->exactly(2))->method('log');

        $this->_cleaner->computeMissingMD5();
    }

    public function testComputeMissingMD5CopyExistsSpaces()
    {
        $url = 'http://bitsavers.org/pdf/dec/jtron/Jumbotron Users Manual.pdf';
        $encodedUrl = 'http://bitsavers.org/pdf/dec/jtron/Jumbotron%20Users%20Manual.pdf';
        $copyId = 5544;
        $docs = DatabaseTester::createResultRowsForColumns([ 'copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url' ],
            [
                [ $copyId, 23, 100, 'Jumobotron Users Manual', $url ]
            ]);
        $this->_db->expects($this->once())->method('getAllMissingMD5Documents')->willReturn($docs);
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($encodedUrl)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(true);
        $copyMD5 = 'deadbeeffacef00d';
        $this->_urlInfo->expects($this->once())->method('md5')->willReturn($copyMD5);
        $this->_db->expects($this->once())->method('updateMD5ForCopy')->with($copyId, $copyMD5);
        $this->_logger->expects($this->exactly(2))->method('log');

        $this->_cleaner->computeMissingMD5();
    }

    public function testComputeMissingMD5CopyDoesNotExist()
    {
        $url = 'http://bitsavers.org/pdf/dec/jtron/Jumbotron_Users_Manual.pdf';
        $copyId = 5544;
        $docs = DatabaseTester::createResultRowsForColumns([ 'copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url' ],
            [
                [ $copyId, 23, 100, 'Jumobotron Users Manual', $url ]
            ]);
        $this->_db->expects($this->once())->method('getAllMissingMD5Documents')->willReturn($docs);
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($url)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(false);
        $this->_db->expects($this->once())->method('updateMD5ForCopy')->with($copyId, '');
        $this->_logger->expects($this->exactly(2))->method('log');

        $this->_cleaner->computeMissingMD5();
    }

    public function testIngest()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = DatabaseTester::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $pubData = $this->stockPubData();
        $copyData = $this->stockCopyData();
        $this->_manx->expects($this->never())->method('getUserFromSession');
        $this->_urlMetaData->expects($this->once())->method('determineIngestData')->with($siteId, $companyId, $url)->willReturn($data);
        $this->_db->expects($this->once())->method('getUnknownPathsForCompanies')->with($siteName)->willReturn($pathRows);
        $pubId = 23;
        $this->_manx->expects($this->never())->method('addPublication')
            ->with($this->_user, $companyId, $data['part'], $data['pub_date'],
                $data['title'], $pubData['pub_type'], $pubData['alt_part'], $pubData['revision'],
                $pubData['keywords'], $pubData['notes'], $pubData['abstract'], $pubData['languages'])
            ->willReturn($pubId);
        $this->_db->expects($this->never())->method('addCopy');
        $this->_db->expects($this->once())->method('markUnknownPathScanned')->with($unknownId);
        $this->_logger->expects($this->exactly(4))->method('log');

        $this->_cleaner->ingest();
    }

    public function testIngestSinglePubsExistForPartAddsCopy()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = DatabaseTester::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $pubId = 23;
        $data['pubs'] = DatabaseTester::createResultRowsForColumns(['pub_id', 'ph_part', 'ph_title'],
            [
                [$pubId, 'EK-3333-01', 'Jumbotron Users Guide'],
            ]);
        $pubData = $this->stockPubData();
        $copyData = $this->stockCopyData();
        $this->_urlMetaData->expects($this->once())->method('determineIngestData')->with($siteId, $companyId, $url)->willReturn($data);
        $copyMD5 = 'deadbeeffacef00d';
        $this->_urlMetaData->expects($this->once())->method('getCopyMD5')->with($url)->willReturn($copyMD5);
        $this->_db->expects($this->once())->method('getUnknownPathsForCompanies')->with($siteName)->willReturn($pathRows);
        $this->_manx->expects($this->never())->method('addPublication');
        $this->_db->expects($this->once())->method('addCopy')->with($pubId, $data['format'], $siteId, $url,
                $copyData['notes'], $data['size'], $copyMD5, $copyData['credits'], $copyData['amend_serial']);
        $this->_db->expects($this->once())->method('markUnknownPathScanned')->with($unknownId);
        $this->_logger->expects($this->exactly(6))->method('log');

        $this->_cleaner->ingest();
    }

    public function testIngestSinglePubsExistForPartWithMismatchedPartSkips()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = DatabaseTester::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $pubId = 23;
        $data['pubs'] = DatabaseTester::createResultRowsForColumns(['pub_id', 'ph_part', 'ph_title'],
            [
                [$pubId, 'EK-3333-02', 'Jumbotron Users Guide'],
            ]);
        $pubData = $this->stockPubData();
        $copyData = $this->stockCopyData();
        $this->_urlMetaData->expects($this->once())->method('determineIngestData')->with($siteId, $companyId, $url)->willReturn($data);
        $this->_urlMetaData->expects($this->never())->method('getCopyMD5');
        $this->_db->expects($this->once())->method('getUnknownPathsForCompanies')->with($siteName)->willReturn($pathRows);
        $this->_manx->expects($this->never())->method('addPublication');
        $this->_db->expects($this->never())->method('addCopy');
        $this->_db->expects($this->once())->method('markUnknownPathScanned')->with($unknownId);
        $this->_logger->expects($this->exactly(5))->method('log');

        $this->_cleaner->ingest();
    }

    public function testIngestMultiplePubsExistForPartSkipsIngestion()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = DatabaseTester::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $data['pubs'] = DatabaseTester::createResultRowsForColumns(['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
            [
                [44, 'EK-3333-01', 'Some Other Manual', '1977-01'],
                [45, 'EK-3333-01', 'Another Unrelated Manual', '1982-04']
            ]);
        $pubData = $this->stockPubData();
        $copyData = $this->stockCopyData();
        $this->_manx->expects($this->never())->method('getUserFromSession');
        $this->_urlMetaData->expects($this->once())->method('determineIngestData')->with($siteId, $companyId, $url)->willReturn($data);
        $this->_db->expects($this->once())->method('getUnknownPathsForCompanies')->with($siteName)->willReturn($pathRows);
        $pubId = 23;
        $this->_manx->expects($this->never())->method('addPublication');
        $this->_db->expects($this->never())->method('addCopy');
        $this->_db->expects($this->once())->method('markUnknownPathScanned')->with($unknownId);
        $this->_logger->expects($this->exactly(5))->method('log');

        $this->_cleaner->ingest();
    }

    public function testIngestCopyExists()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = DatabaseTester::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $data['exists'] = true;
        $pubId = 23;
        $data['ph_pub'] = $pubId;
        $pubData = $this->stockPubData();
        $copyData = $this->stockCopyData();
        $user = $this->createMock(IUser::class);
        $this->_manx->expects($this->never())->method('getUserFromSession');
        $this->_urlMetaData->expects($this->never())->method('determineData')->with($url)->willReturn($data);
        $this->_urlMetaData->expects($this->once())->method('determineIngestData')->with($siteId, $companyId, $url)->willReturn($data);
        $this->_db->expects($this->once())->method('getUnknownPathsForCompanies')->with($siteName)->willReturn($pathRows);
        $this->_manx->expects($this->never())->method('addPublication');
        $this->_db->expects($this->never())->method('addCopy');
        $this->_db->expects($this->once())->method('markUnknownPathScanned')->with($unknownId);
        $this->_logger->expects($this->exactly(4))->method('log');

        $this->_cleaner->ingest();
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
