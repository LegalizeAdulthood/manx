<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class WhatsNewCleanerTest extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;

    /** @var Manx\IManxDatabase */
    private $_db;
    /** @var Manx\IManx */
    private $_manx;
    /** @var Manx\IUrlInfo */
    private $_urlInfo;
    /** @var Manx\IWhatsNewPageFactory */
    private $_factory;
    /** @var Manx\Cron\ILogger */
    private $_logger;
    /** @var Manx\IPdfMetadata */
    private $_pdfMetadata;
    /** @var Manx\IDateTimeProvider */
    private $_dateTimeProvider;
    /** @var Manx\IUser */
    private $_user;
    /** @var Manx\Cron\BitSaversCleaner */
    private $_cleaner;
    /** @var Manx\IWhatsNewIndex */
    private $_whatsNewIndex;

    protected function setUp(): void
    {
        $this->_urlInfo = $this->createMock(Manx\IUrlInfo::class);
        $this->_factory = $this->createMock(Manx\IWhatsNewPageFactory::class);
        $this->_logger = $this->createMock(Manx\Cron\ILogger::class);

        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_manx = $this->createMock(Manx\IManx::class);
        $this->_manx->expects($this->atLeast(1))->method('getDatabase')->willReturn($this->_db);
        $this->_whatsNewIndex = $this->createMock(Manx\IWhatsNewIndex::class);
        $this->_urlMetaData = $this->createMock(Manx\IUrlMetaData::class);
        $this->_pdfMetadata = $this->createMock(Manx\IPdfMetadata::class);
        $this->_dateTimeProvider =
            $this->createMock(Manx\IDateTimeProvider::class);
        $this->_user = $this->createMock(Manx\IUser::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['logger'] = $this->_logger;
        $config['whatsNewPageFactory'] = $this->_factory;
        $config['whatsNewIndex'] = $this->_whatsNewIndex;
        $config['fileSystem'] = $this->createMock(Manx\IFileSystem::class);
        $config['urlMetaData'] = $this->_urlMetaData;
        $config['pdfMetadata'] = $this->_pdfMetadata;
        $config['dateTimeProvider'] = $this->_dateTimeProvider;
        $config['user'] = $this->_user;
        $this->_config = $config;
        $this->_cleaner = new Manx\Cron\BitSaversCleaner($this->_config);
    }

    public function testNonExistentPathsAreRemoved()
    {
        $this->_db->expects($this->once())->method('getAllSiteUnknownPaths')
            ->with('bitsavers')
            ->willReturn( array( array('id' => 1, 'path' => 'foo/path.pdf') ) );
        $this->_db->expects($this->once())->method('removeSiteUnknownPathById')->with(1);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(false);
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with('http://bitsavers.trailing-edge.com/pdf/foo/path.pdf')
            ->willReturn($this->_urlInfo);
        $this->_logger->expects($this->exactly(3))->method('log');

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
        $this->_logger->expects($this->exactly(2))->method('log');

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
                    'candidate_url' => 'http://bitsavers.org/pdf/hp/newDir/foo.pdf',
                    'url' => 'http://bitsavers.org/pdf/hp/foo.pdf', 'copy_id' => 10,
                    'size' => 1234, 'md5' => $md5]
            ]);
        $this->_db->expects($this->once())->method('siteFileMoved')
            ->with(16, 10, 'http://bitsavers.org/pdf/hp/newDir/foo.pdf');
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with('http://bitsavers.trailing-edge.com/pdf/hp/newDir/foo.pdf')
            ->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('md5')->willReturn($md5);
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(1234);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(true);

        $this->_cleaner->updateMovedFiles();
    }

    public function testMovedFilesWithDifferentSizesAreNotHashed()
    {
        $md5 = '37e10bd2e8da6bd96eb3a72feeea56ee';
        $this->_db->expects($this->once())->method('getPossiblyMovedSiteUnknownPaths')
            ->with('bitsavers')
            ->willReturn( [
                ['path' => 'hp/newDir/foo.pdf', 'path_id' => 16,
                    'candidate_url' => 'http://bitsavers.org/pdf/hp/newDir/foo.pdf',
                    'url' => 'http://bitsavers.org/pdf/hp/foo.pdf', 'copy_id' => 10,
                    'size' => 1234, 'md5' => $md5]
            ]);
        $this->_db->expects($this->never())->method('siteFileMoved');
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with('http://bitsavers.trailing-edge.com/pdf/hp/newDir/foo.pdf')
            ->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->never())->method('md5');
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(4321);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(true);

        $this->_cleaner->updateMovedFiles();
    }

    public function testMovedFilesWithUnchangedUrlsAreSkipped()
    {
        $md5 = '37e10bd2e8da6bd96eb3a72feeea56ee';
        $url = 'http://bitsavers.org/pdf/hp/newDir/foo.pdf';
        $this->_db->expects($this->once())->method('getPossiblyMovedSiteUnknownPaths')
            ->with('bitsavers')
            ->willReturn( [
                ['path' => 'hp/newDir/foo.pdf', 'path_id' => 16,
                    'candidate_url' => $url, 'url' => $url, 'copy_id' => 10,
                    'size' => 1234, 'md5' => $md5]
            ]);
        $this->_db->expects($this->never())->method('siteFileMoved');
        $this->_factory->expects($this->never())->method('createUrlInfo');

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
        $docs = \Manx\Test\RowFactory::createResultRowsForColumns([ 'copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url' ],
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
        $docs = \Manx\Test\RowFactory::createResultRowsForColumns([ 'copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url' ],
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

    public function testComputeMissingMD5CopyExistsHash()
    {
        $url = 'http://bitsavers.org/pdf/dec/foo#bar/EK-3333#1.pdf';
        $encodedUrl = 'http://bitsavers.org/pdf/dec/foo%23bar/EK-3333%231.pdf';
        $copyId = 5544;
        $docs = \Manx\Test\RowFactory::createResultRowsForColumns([ 'copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url' ],
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

    public function testComputeMissingMD5CopyExistsEncodedHash()
    {
        $url = 'http://bitsavers.org/pdf/dec/foo%23bar/EK-3333%231.pdf';
        $copyId = 5544;
        $docs = \Manx\Test\RowFactory::createResultRowsForColumns([ 'copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url' ],
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

    public function testComputeMissingMD5SpecialCharacterCopyDoesNotExist()
    {
        $url = 'http://bitsavers.org/pdf/dec/foo#bar/EK-3333#1.pdf';
        $encodedUrl = 'http://bitsavers.org/pdf/dec/foo%23bar/EK-3333%231.pdf';
        $copyId = 5544;
        $docs = \Manx\Test\RowFactory::createResultRowsForColumns([ 'copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url' ],
            [
                [ $copyId, 23, 100, 'Jumobotron Users Manual', $url ]
            ]);
        $this->_db->expects($this->once())->method('getAllMissingMD5Documents')->willReturn($docs);
        $this->_factory->expects($this->once())->method('createUrlInfo')->with($encodedUrl)->willReturn($this->_urlInfo);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(false);
        $this->_urlInfo->expects($this->never())->method('md5');
        $this->_db->expects($this->once())->method('updateMD5ForCopy')->with($copyId, '');
        $this->_logger->expects($this->exactly(2))->method('log');

        $this->_cleaner->computeMissingMD5();
    }

    public function testComputeMissingMD5CopyDoesNotExist()
    {
        $url = 'http://bitsavers.org/pdf/dec/jtron/Jumbotron_Users_Manual.pdf';
        $copyId = 5544;
        $docs = \Manx\Test\RowFactory::createResultRowsForColumns([ 'copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url' ],
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
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
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
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $pubId = 23;
        $data['pubs'] = \Manx\Test\RowFactory::createResultRowsForColumns(['pub_id', 'ph_part', 'ph_title'],
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

    public function testIngestUsesDirectoryPartRegex()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $partRegex = '^Guide_([^_]+)_';
        $url = 'http://bitsavers.org/pdf/dec/foo/Guide_EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'company_id', 'part_regex', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, $partRegex, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $data['part'] = 'EK-3333-01';
        $data['pub_date'] = '1977-02';
        $data['title'] = 'Jumbotron Users Guide';
        $pubId = 23;
        $data['pubs'] = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['pub_id', 'ph_part', 'ph_title'],
            [
                [$pubId, 'EK-3333-01', 'Jumbotron Users Guide'],
            ]);
        $copyData = $this->stockCopyData();
        $this->_urlMetaData->expects($this->once())
            ->method('determineIngestData')
            ->with($siteId, $companyId, $url, $partRegex)
            ->willReturn($data);
        $copyMD5 = 'deadbeeffacef00d';
        $this->_urlMetaData->expects($this->once())->method('getCopyMD5')
            ->with($url)->willReturn($copyMD5);
        $this->_db->expects($this->once())
            ->method('getUnknownPathsForCompanies')
            ->with($siteName)->willReturn($pathRows);
        $this->_manx->expects($this->never())->method('addPublication');
        $this->_db->expects($this->once())->method('addCopy')
            ->with($pubId, $data['format'], $siteId, $url,
                $copyData['notes'], $data['size'], $copyMD5,
                $copyData['credits'], $copyData['amend_serial']);
        $this->_db->expects($this->once())->method('markUnknownPathScanned')
            ->with($unknownId);
        $this->_logger->expects($this->exactly(6))->method('log');

        $this->_cleaner->ingest();
    }

    public function testIngestEmptyPartRegexUsesDefaultExtraction()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'company_id', 'part_regex', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, '', 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $this->_manx->expects($this->never())->method('getUserFromSession');
        $this->_urlMetaData->expects($this->once())
            ->method('determineIngestData')
            ->with($siteId, $companyId, $url)->willReturn($data);
        $this->_db->expects($this->once())
            ->method('getUnknownPathsForCompanies')
            ->with($siteName)->willReturn($pathRows);
        $this->_manx->expects($this->never())->method('addPublication');
        $this->_db->expects($this->never())->method('addCopy');
        $this->_db->expects($this->once())->method('markUnknownPathScanned')
            ->with($unknownId);
        $this->_logger->expects($this->exactly(4))->method('log');

        $this->_cleaner->ingest();
    }

    public function testIngestPartRegexNoMatchSkips()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $partRegex = '^Guide_([^_]+)_';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'company_id', 'part_regex', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, $partRegex, 'dec', $url]
            ]);
        $this->_urlMetaData->expects($this->never())
            ->method('determineIngestData');
        $this->_urlMetaData->expects($this->never())->method('getCopyMD5');
        $this->_db->expects($this->once())
            ->method('getUnknownPathsForCompanies')
            ->with($siteName)->willReturn($pathRows);
        $this->_manx->expects($this->never())->method('addPublication');
        $this->_db->expects($this->never())->method('addCopy');
        $this->_db->expects($this->once())->method('markUnknownPathScanned')
            ->with($unknownId);
        $this->_logger->expects($this->exactly(4))->method('log');

        $this->_cleaner->ingest();
    }

    public function testIngestInvalidPartRegexSkips()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $partRegex = '([broken';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'company_id', 'part_regex', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, $partRegex, 'dec', $url]
            ]);
        $this->_urlMetaData->expects($this->never())
            ->method('determineIngestData');
        $this->_urlMetaData->expects($this->never())->method('getCopyMD5');
        $this->_db->expects($this->once())
            ->method('getUnknownPathsForCompanies')
            ->with($siteName)->willReturn($pathRows);
        $this->_manx->expects($this->never())->method('addPublication');
        $this->_db->expects($this->never())->method('addCopy');
        $this->_db->expects($this->once())->method('markUnknownPathScanned')
            ->with($unknownId);
        $this->_logger->expects($this->exactly(4))->method('log');

        $this->_cleaner->ingest();
    }

    public function testIngestSinglePubsExistForPartWithMismatchedPartSkips()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $pubId = 23;
        $data['pubs'] = \Manx\Test\RowFactory::createResultRowsForColumns(['pub_id', 'ph_part', 'ph_title'],
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
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $data['pubs'] = \Manx\Test\RowFactory::createResultRowsForColumns(['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
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
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $data['exists'] = true;
        $pubId = 23;
        $data['ph_pub'] = $pubId;
        $pubData = $this->stockPubData();
        $copyData = $this->stockCopyData();
        $user = $this->createMock(Manx\IUser::class);
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

    public function testIngestSpecialCharacterCopyExists()
    {
        $unknownId = 66;
        $companyId = 13;
        $siteId = 3;
        $siteName = 'bitsavers';
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron #1 Guide_Feb1977.pdf';
        $pathRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'company_id', 'directory', 'url'],
            [
                [$unknownId, $siteId, $companyId, 'dec', $url]
            ]);
        $data = $this->bitsaversMetaData($siteId, $companyId, $url);
        $data['exists'] = true;
        $data['ph_pub'] = 23;
        $this->_manx->expects($this->never())->method('getUserFromSession');
        $this->_urlMetaData->expects($this->once())->method('determineIngestData')
            ->with($siteId, $companyId, $url)->willReturn($data);
        $this->_db->expects($this->once())->method('getUnknownPathsForCompanies')
            ->with($siteName)->willReturn($pathRows);
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

    public function testUpdateIgnoredUnknownDirs()
    {
        $this->_db->expects($this->once())->method('updateIgnoredUnknownDirs');
        $this->_logger->expects($this->once())->method('log');

        $this->_cleaner->updateIgnoredUnknownDirs();
    }

    public function testCachePdfMetadataStoresOkResult()
    {
        $unknownId = 66;
        $url = 'http://bitsavers.org/pdf/dec/foo/EK 3333.pdf';
        $encodedUrl = 'http://bitsavers.org/pdf/dec/foo/EK%203333.pdf';
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'url'],
            [
                [$unknownId, $url]
            ]);
        $metadata = [
            'status' => Manx\PdfMetadata::STATUS_OK,
            'title' => 'Title',
            'keywords' => 'keywords',
            'abstract' => 'Abstract',
            'copy_notes' => 'Notes',
            'copy_credits' => 'Credits'
        ];
        $this->_dateTimeProvider->expects($this->exactly(2))
            ->method('now')
            ->willReturn(self::dateAt(1000), self::dateAt(1000));
        $this->_db->expects($this->once())
            ->method('getUnknownPdfMetadataPaths')
            ->with('bitsavers')->willReturn($rows);
        $this->_pdfMetadata->expects($this->once())
            ->method('metadataForUrl')
            ->with($encodedUrl)->willReturn($metadata);
        $this->_db->expects($this->once())
            ->method('updateSiteUnknownPdfMetadata')
            ->with($unknownId, 'Title', 'keywords', 'Abstract', 'Notes',
                'Credits', 'ok', '');
        $this->_logger->expects($this->exactly(3))->method('log');

        $this->_cleaner->cachePdfMetadata(1800);
    }

    public function testCachePdfMetadataStoresNoneResult()
    {
        $unknownId = 66;
        $url = 'http://bitsavers.org/pdf/dec/foo/EK-3333.pdf';
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'url'],
            [
                [$unknownId, $url]
            ]);
        $this->_dateTimeProvider->method('now')->willReturn(self::dateAt(0));
        $this->_db->expects($this->once())
            ->method('getUnknownPdfMetadataPaths')
            ->with('bitsavers')->willReturn($rows);
        $this->_pdfMetadata->expects($this->once())
            ->method('metadataForUrl')
            ->with($url)
            ->willReturn(Manx\PdfMetadata::emptyResult(
                Manx\PdfMetadata::STATUS_EMPTY));
        $this->_db->expects($this->once())
            ->method('updateSiteUnknownPdfMetadata')
            ->with($unknownId, '', '', '', '', '', 'none', '');

        $this->_cleaner->cachePdfMetadata(1800);
    }

    public function testCachePdfMetadataStoresErrorAndContinues()
    {
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'url'],
            [
                [66, 'http://bitsavers.org/pdf/dec/foo/broken.pdf'],
                [67, 'http://bitsavers.org/pdf/dec/foo/ok.pdf']
            ]);
        $ok = [
            'status' => Manx\PdfMetadata::STATUS_OK,
            'title' => 'OK',
            'keywords' => '',
            'abstract' => '',
            'copy_notes' => '',
            'copy_credits' => ''
        ];
        $this->_dateTimeProvider->method('now')->willReturn(self::dateAt(0));
        $this->_db->expects($this->once())
            ->method('getUnknownPdfMetadataPaths')
            ->with('bitsavers')->willReturn($rows);
        $this->_pdfMetadata->expects($this->exactly(2))
            ->method('metadataForUrl')
            ->willReturnCallback(function($url) use ($ok) {
                if ($url == 'http://bitsavers.org/pdf/dec/foo/broken.pdf')
                {
                    throw new RuntimeException('parse failed');
                }
                return $ok;
            });
        $this->_db->expects($this->exactly(2))
            ->method('updateSiteUnknownPdfMetadata')
            ->withConsecutive(
                [66, '', '', '', '', '', 'error', 'parse failed'],
                [67, 'OK', '', '', '', '', 'ok', '']);

        $this->_cleaner->cachePdfMetadata(1800);
    }

    public function testCachePdfMetadataHonorsTimeLimit()
    {
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'url'],
            [
                [66, 'http://bitsavers.org/pdf/dec/foo/first.pdf'],
                [67, 'http://bitsavers.org/pdf/dec/foo/second.pdf']
            ]);
        $this->_dateTimeProvider->expects($this->exactly(3))
            ->method('now')
            ->willReturn(self::dateAt(0), self::dateAt(0), self::dateAt(31));
        $this->_db->expects($this->once())
            ->method('getUnknownPdfMetadataPaths')
            ->with('bitsavers')->willReturn($rows);
        $this->_pdfMetadata->expects($this->once())
            ->method('metadataForUrl')
            ->with('http://bitsavers.org/pdf/dec/foo/first.pdf')
            ->willReturn(Manx\PdfMetadata::emptyResult(
                Manx\PdfMetadata::STATUS_EMPTY));
        $this->_db->expects($this->once())
            ->method('updateSiteUnknownPdfMetadata')
            ->with(66, '', '', '', '', '', 'none', '');

        $this->_cleaner->cachePdfMetadata(30);
    }

    private static function dateAt($timestamp)
    {
        return new DateTime('@' . $timestamp);
    }
}
