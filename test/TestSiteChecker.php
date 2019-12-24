<?php

require_once 'vendor/autoload.php';

require_once 'cron/SiteChecker.php';

use Pimple\Container;

class TestSiteChecker extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;

    /** @var Manx\IManxDatabase */
    private $_db;
    /** @var Manx\IManx */
    private $_manx;
    /** @var Manx\IUrlInfo */
    private $_urlInfo;
    /** @var Manx\IUrlInfoFactory */
    private $_factory;
    /** @var Manx\Cron\ILogger */
    private $_logger;
    /** @var Manx\IUser */
    private $_user;
    /** @var SiteChecker */
    private $_checker;

    protected function setUp()
    {
        $this->_urlInfo = $this->createMock(Manx\IUrlInfo::class);
        $this->_factory = $this->createMock(Manx\IUrlInfoFactory::class);
        $this->_logger = $this->createMock(Manx\Cron\ILogger::class);
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_manx = $this->createMock(Manx\IManx::class);
        $this->_manx->expects($this->atLeast(1))->method('getDatabase')->willReturn($this->_db);
        $this->_user = $this->createMock(Manx\IUser::class);

        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['urlInfoFactory'] = $this->_factory;
        $config['logger'] = $this->_logger;
        $config['user'] = $this->_user;
        $this->_config = $config;
        $this->_checker = new SiteChecker($this->_config);
    }

    public function testCheckSiteOnlineWhenSiteAndDocsExist()
    {
        $siteUrl = 'http://bitsavers.org/pdf';
        $docUrl = 'http://bitsavers.org/pdf/dec/jumbotron.pdf';
        $docUrlInfo = $this->createMock(Manx\IUrlInfo::class);
        $this->_factory->expects($this->exactly(2))->method('createUrlInfo')->withConsecutive([$siteUrl], [$docUrl])->willReturn($this->_urlInfo, $docUrlInfo);
        $this->_urlInfo->expects($this->once())->method('exists')->willReturn(true);
        $this->_urlInfo->method('url')->willReturn($siteUrl);
        $siteId = 3;
        $siteRows = DatabaseTester::createResultRowsForColumns(
            ['site_id', 'name', 'url', 'description', 'copy_base', 'low', 'live', 'display_order'],
            [
                [$siteId, 'bitsavers', $siteUrl, '', '', 'N', 'Y', 0]
            ]);
        $this->_db->expects($this->once())->method('getSites')->willReturn($siteRows);
        $urlRows = DatabaseTester::createResultRowsForColumns([ 'url' ], [ [ $docUrl ] ]);
        $this->_db->expects($this->once())->method('getSampleCopiesForSite')->with($siteId)->willReturn($urlRows);
        $docUrlInfo->expects($this->once())->method('exists')->willReturn(true);
        $this->_db->expects($this->once())->method('setSiteLive')->with($siteId, true);
        $this->_logger->expects($this->exactly(3))->method('log');

        $this->_checker->checkSites();
    }
}
