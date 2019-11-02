<?php

require_once 'pages/UrlMetaData.php';
require_once 'test/DatabaseTester.php';

use Pimple\Container;

class TestUrlMetaData extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->_manx = $this->createMock(IManx::class);
        $this->_db = $this->createMock(IManxDatabase::class);
        $this->_urlInfoFactory = $this->createMock(IUrlInfoFactory::class);
        $this->_urlInfo = $this->createMock(IUrlInfo::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['db'] = $this->_db;
        $config['urlInfoFactory'] = $this->_urlInfoFactory;
        $this->_config = $config;
        $this->_meta = new UrlMetaData($config);
    }

    public function testConstruct()
    {
        $this->assertFalse(is_null($this->_meta));
        $this->assertTrue(is_object($this->_meta));
    }

    public function testDetermineIngestDataForBitSaversNoCopy()
    {
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->willReturn($this->_urlInfo);
        $copySize = 4096;
        $this->_urlInfo->expects($this->once())->method('size')->willReturn($copySize);
        $siteId = 3;
        $bitsaversSiteRow = $this->bitSaversSiteRow($siteId);
        $this->_db->expects($this->once())->method('getSites')->willReturn([$bitsaversSiteRow]);
        $url = 'http://bitsavers.org/pdf/microdata/periph/2602_Bisync_Controller/PS20002602_2602_Bisync_Interface_Product_Specification_Mar1977.pdf';
        $companyId = 13;
        $part = 'PS20002602';
        $this->_db->expects($this->once())->method('getPublicationsForPartNumber')->with($part, $companyId)->willReturn([]);
        $pubId = 23;
        $title = '2602 Bisync Interface Product Specification';
        $copyExistsRow = ['ph_company' => $companyId, 'ph_pub' => $pubId, 'ph_title' => $title ];
        $this->_db->expects($this->once())->method('copyExistsForUrl')->with($url)->willReturn($copyExistsRow);
        $this->_db->expects($this->never())->method('getCompanyForSiteDirectory');
        $this->_db->expects($this->never())->method('getFormatForExtension');
        $this->_db->expects($this->never())->method('getMirrors');

        $data = $this->_meta->determineIngestData($siteId, $companyId, $url);

        $expectedData = [
            'size' => $copySize,
            'valid' => true,
            'site' => $bitsaversSiteRow,
            'company' => 13,
            'part' => $part,
            'pub_date' => '1977-03',
            'title' => $title,
            'pubs' => [],
            'exists' => true,
            'pub_id' => $pubId
            ];
        $this->assertEquals($expectedData, $data);
    }

    public function testDetermineDataForBitSaversNoCopy()
    {
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->willReturn($this->_urlInfo);
        $copySize = 4096;
        $this->_urlInfo->expects($this->once())->method('size')->willReturn($copySize);
        $siteId = 3;
        $bitsaversSiteRow = $this->bitSaversSiteRow($siteId);
        $this->_db->expects($this->once())->method('getSites')->willReturn([$bitsaversSiteRow]);
        $url = 'http://bitsavers.org/pdf/microdata/periph/2602_Bisync_Controller/PS20002602_2602_Bisync_Interface_Product_Specification_Mar1977.pdf';
        $companyId = 13;
        $part = 'PS20002602';
        $this->_db->expects($this->once())->method('getCompanyForSiteDirectory')->with('bitsavers', 'microdata')->willReturn($companyId);
        $this->_db->expects($this->once())->method('getPublicationsForPartNumber')->with($part, $companyId)->willReturn([]);
        $this->_db->expects($this->once())->method('getFormatForExtension')->with('pdf')->willReturn('PDF');
        $pubId = 23;
        $title = '2602 Bisync Interface Product Specification';
        $copyExistsRow = ['ph_company' => $companyId, 'ph_pub' => $pubId, 'ph_title' => $title ];
        $this->_db->expects($this->once())->method('copyExistsForUrl')->with($url)->willReturn($copyExistsRow);
        $this->_db->expects($this->never())->method('getMirrors');

        $data = $this->_meta->determineData($url);

        $expectedData = [
            'url' => $url,
            'mirror_url' => '',
            'size' => $copySize,
            'valid' => true,
            'site' => $bitsaversSiteRow,
            'company' => 13,
            'part' => $part,
            'pub_date' => '1977-03',
            'title' => $title,
            'format' => 'PDF',
            'site_company_directory' => 'microdata',
            'pubs' => [],
            'exists' => true,
            'pub_id' => $pubId
            ];
        $this->assertEquals($expectedData, $data);
    }

    public function testDetermineDataForBitSaversExistingCopy()
    {
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->willReturn($this->_urlInfo);
        $copySize = 4096;
        $this->_urlInfo->expects($this->once())->method('size')->willReturn($copySize);
        $siteId = 3;
        $bitsaversSiteRow = $this->bitSaversSiteRow($siteId);
        $this->_db->expects($this->once())->method('getSites')->willReturn([$bitsaversSiteRow]);
        $url = 'http://bitsavers.org/pdf/microdata/periph/2602_Bisync_Controller/PS20002602_2602_Bisync_Interface_Product_Specification_Mar1977.pdf';
        $companyId = 13;
        $part = 'PS20002602';
        $this->_db->expects($this->once())->method('getCompanyForSiteDirectory')->with('bitsavers', 'microdata')->willReturn($companyId);
        $this->_db->expects($this->once())->method('getPublicationsForPartNumber')->with($part, $companyId)->willReturn([]);
        $this->_db->expects($this->once())->method('getFormatForExtension')->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->once())->method('copyExistsForUrl')->with($url)->willReturn(true);
        $this->_db->expects($this->never())->method('getMirrors');

        $data = $this->_meta->determineData($url);

        $expectedData = [
            'url' => $url,
            'mirror_url' => '',
            'size' => $copySize,
            'valid' => true,
            'site' => $bitsaversSiteRow,
            'company' => null,
            'part' => $part,
            'pub_date' => '1977-03',
            'title' => null,
            'format' => 'PDF',
            'site_company_directory' => 'microdata',
            'pubs' => [],
            'exists' => true,
            'pub_id' => null
        ];
        $this->assertEquals($expectedData, $data);
    }

    private function bitSaversSiteRow($siteId)
    {
        return [
            'site_id' => $siteId,
            'name' => 'bitsavers',
            'url' => 'http://bitsavers.org',
            'description' => '',
            'copy_base' => 'http://bitsavers.org/pdf/',
            'low' => 'N',
            'live' => 'Y',
            'display_order' => 1
        ];
    }

    private $_manx;
    private $_db;
    private $_urlInfoFactory;
    private $_urlInfo;
    private $_config;
    private $_meta;
}
