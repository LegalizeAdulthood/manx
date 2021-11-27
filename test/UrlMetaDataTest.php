<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class UrlMetaDataTest extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $this->_manx = $this->createMock(Manx\IManx::class);
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_urlInfoFactory = $this->createMock(Manx\IUrlInfoFactory::class);
        $this->_urlInfo = $this->createMock(Manx\IUrlInfo::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['db'] = $this->_db;
        $config['urlInfoFactory'] = $this->_urlInfoFactory;
        $this->_config = $config;
        $this->_meta = new Manx\UrlMetaData($config);
    }

    public function testConstruct()
    {
        $this->assertFalse(is_null($this->_meta));
        $this->assertTrue(is_object($this->_meta));
    }

    public function testGetCopyMD5()
    {
        $url = 'http://bitsavers.org/pdf/microdata/periph/2602_Bisync_Controller/PS20002602_2602_Bisync_Interface_Product_Specification_Mar1977.pdf';
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->with($url)->willReturn($this->_urlInfo);
        $copyMD5 = 'deadbeeffacef00d';
        $this->_urlInfo->expects($this->once())->method('md5')->willReturn($copyMD5);

        $md5 = $this->_meta->getCopyMD5($url);

        $this->assertEquals($copyMD5, $md5);
    }

    public function testDetermineIngestDataForBitSaversNoCopy()
    {
        $url = 'http://bitsavers.org/pdf/microdata/periph/2602_Bisync_Controller/PS20002602_2602_Bisync_Interface_Product_Specification_Mar1977.pdf';
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->with($url)->willReturn($this->_urlInfo);
        $copySize = 4096;
        $this->_urlInfo->expects($this->once())->method('size')->willReturn($copySize);
        $siteId = 3;
        $bitsaversSiteRow = $this->bitSaversSiteRow($siteId);
        $this->_db->expects($this->once())->method('getSites')->willReturn([$bitsaversSiteRow]);
        $companyId = 13;
        $part = 'PS20002602';
        $this->_db->expects($this->once())->method('getPublicationsForPartNumber')->with($part, $companyId)->willReturn([]);
        $pubId = 23;
        $title = '2602 Bisync Interface Product Specification';
        $copyExistsRow = ['ph_company' => $companyId, 'ph_pub' => $pubId, 'ph_title' => $title ];
        $this->_db->expects($this->once())->method('copyExistsForUrl')->with($url)->willReturn($copyExistsRow);
        $this->_db->expects($this->never())->method('getCompanyIdForSiteDirectory');
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
            'pub_id' => $pubId,
        ];
        $this->assertEquals($expectedData, $data);
    }

    public function testDetermineDataNonExistentUrl()
    {
        $url = 'http://bitsavers.org/pdf/sandersAssociates/graphic7/Graphic_7_Monitor_Preliminary_Users_Guide_May_1979.pdf';
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(false);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with($url)->willReturn($this->_urlInfo);

        $data = $this->_meta->determineData($url);

        $this->assertEquals(['valid' => false], $data);
    }

    public function testDetermineDataNewBitSaversCompany()
    {
        $siteId = 3;
        $this->_db->expects($this->once())->method('getSites')->willReturn(self::sitesResultsForBitSavers($siteId));
        $this->_db->expects($this->once())->method('getCompanyIdForSiteDirectory')->willReturn('-1');
        $this->_db->expects($this->once())->method('getFormatForExtension')->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->never())->method('getPublicationsForPartNumber');
        $this->_db->expects($this->never())->method('searchForPublications');
        $url = 'http://bitsavers.org/pdf/sandersAssociates/graphic7/Graphic_7_Monitor_Preliminary_Users_Guide_May_1979.pdf';
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->with($url)->willReturn($this->_urlInfo);

        $data = $this->_meta->determineData($url);

        $expectedData = [
            'url' => $url,
            'mirror_url' => '',
            'size' => 1266,
            'valid' => true,
            'site' => self::bitSaversSiteRow($siteId),
            'company' => '-1',
            'part' => '',
            'pub_date' => '1979-05',
            'title' => 'Graphic 7 Monitor Preliminary Users Guide',
            'format' => 'PDF',
            'site_company_directory' => 'sandersAssociates',
            'site_company_parent_directory' => '',
            'pubs' => [],
            'keywords' => 'Graphic 7 Monitor Preliminary Users Guide'
        ];
        $this->assertEquals($expectedData, $data);
    }

    public function testDetermineDataMirrorUrl()
    {
        $siteId = 3;
        $this->_db->expects($this->once())->method('getSites')->willReturn(self::sitesResultsForBitSavers($siteId));
        $this->_db->expects($this->once())->method('getCompanyIdForSiteDirectory')->with('bitsavers', 'tektronix', '')->willReturn('5');
        $this->_db->expects($this->once())->method('getFormatForExtension')->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->once())->method('getMirrors')->willReturn([
            self::databaseRowFromDictionary([
                'mirror_id' => '2',
                'site' => '3',
                'original_stem' => 'http://bitsavers.org/',
                'copy_stem' => 'http://bitsavers.trailing-edge.com/',
                'rank' => '9'
            ])
        ]);
        $this->_db->expects($this->once())->method('getPublicationsForPartNumber')->with('070-1183-01', '5')->willReturn([]);
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with('http://bitsavers.trailing-edge.com/pdf/tektronix/401x/070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf')
            ->willReturn($this->_urlInfo);
        $urlBase = '/pdf/tektronix/401x/070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf';
        $url = 'http://bitsavers.trailing-edge.com' . $urlBase;

        $data = $this->_meta->determineData($url);

        $expected = [
            'url' => 'http://bitsavers.org' . $urlBase,
            'mirror_url' => $url,
            'size' => 1266,
            'valid' => true,
            'site' => self::bitSaversSiteRow($siteId),
            'company' => '5',
            'part' => '070-1183-01',
            'pub_date' => '1976-04',
            'title' => 'Rev B 4010 Maintenance Manual',
            'format' => 'PDF',
            'site_company_directory' => 'tektronix',
            'site_company_parent_directory' => '',
            'pubs' => [],
            'keywords' => '070-1183-01 Rev B 4010 Maintenance Manual'
        ];
        $this->assertEquals($data, $expected);
    }

    public function testDetermineDataWwwBitSaversOrg()
    {
        $siteId = 3;
        $this->_db->expects($this->once())->method('getSites')->willReturn(self::sitesResultsForBitSavers($siteId));
        $this->_db->expects($this->once())->method('getCompanyIdForSiteDirectory')->with('bitsavers', 'univac', '')->willReturn('-1');
        $this->_db->expects($this->once())->method('getFormatForExtension')->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->never())->method('getPublicationsForPartNumber');
        $this->_db->expects($this->never())->method('searchForPublications');
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with('http://bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf')
            ->willReturn($this->_urlInfo);
        $urlBase = '/pdf/univac/1100/UE-637_1108execUG_1970.pdf';
        $url = 'http://bitsavers.org' . $urlBase;

        $data = $this->_meta->determineData($url);

        $expected = [
            'url' => $url,
            'mirror_url' => '',
            'size' => 1266,
            'valid' => true,
            'site' => self::bitsaversSiteRow($siteId),
            'company' => '-1',
            'part' => 'UE-637',
            'pub_date' => '1970',
            'title' => '1108exec UG',
            'format' => 'PDF',
            'site_company_directory' => 'univac',
            'site_company_parent_directory' => '',
            'pubs' => [],
            'keywords' => 'UE-637 1108exec UG'
        ];
        $this->assertEquals($expected, $data);
    }

    public function testDetermineDataVtda()
    {
        $this->_db->expects($this->once())->method('getSites')->willReturn(self::sitesResultsForVtda());
        $this->_db->expects($this->once())->method('getCompanyIdForSiteDirectory')->with('VTDA', 'Motorola', 'computing')->willReturn('66');
        $this->_db->expects($this->once())->method('getFormatForExtension')->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->once())->method('getPublicationsForPartNumber')->with('6064A-5M-668', '66')->willReturn(array());
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $urlBase = '/docs/computing/Motorola/6064A-5M-668_MDR-1000Brochure.pdf';
        $url = 'http://vtda.org' . $urlBase;
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->with($url)->wilLReturn($this->_urlInfo);

        $data = $this->_meta->determineData($url);

        $expected = [
            'url' => $url,
            'mirror_url' => '',
            'size' => 1266,
            'valid' => true,
            'site' => self::vtdaSiteRow(),
            'company' => '66',
            'part' => '6064A-5M-668',
            'pub_date' => '',
            'title' => 'MDR-1000Brochure',
            'format' => 'PDF',
            'site_company_directory' => 'Motorola',
            'site_company_parent_directory' => 'computing',
            'pubs' => [],
            'keywords' => '6064A-5M-668 MDR-1000Brochure'
        ];
        $this->assertEquals($data, $expected);
    }

    public function testDetermineDataVtdaUrlWithSpaces()
    {
        $this->_db->expects($this->once())->method('getSites')->willReturn(self::sitesResultsForVtda());
        $companyId = 66;
        $this->_db->expects($this->once())->method('getCompanyIdForSiteDirectory')->with('VTDA', 'Sun', 'computing')->willReturn($companyId);
        $this->_db->expects($this->once())->method('getFormatForExtension')->with('pdf')->willReturn('PDF');
        $part = '800-1023-01';
        $this->_db->expects($this->once())->method('getPublicationsForPartNumber')->with($part, $companyId)->willReturn(array());
        $copySize = 1266;
        $this->_urlInfo->expects($this->once())->method('size')->willReturn($copySize);
        $urlBase = '/docs/computing/Sun/hardware/800-1023-01_Adaptec%20ACB%204000%20and%205000%20Series%20Disk%20Controllers%20OEM%20Manual%20(Preliminary).pdf';
        $url = 'http://vtda.org' . $urlBase;
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->with($url)->wilLReturn($this->_urlInfo);

        $data = $this->_meta->determineData($url);

        $expected = [
            'url' => $url,
            'mirror_url' => '',
            'size' => $copySize,
            'valid' => true,
            'site' => self::vtdaSiteRow(),
            'company' => $companyId,
            'part' => $part,
            'pub_date' => '',
            'title' => 'Adaptec ACB 4000 and 5000 Series Disk Controllers OEM Manual (Preliminary)',
            'format' => 'PDF',
            'site_company_directory' => 'Sun',
            'site_company_parent_directory' => 'computing',
            'pubs' => [],
            'keywords' => $part . ' Adaptec ACB 4000 and 5000 Series Disk Controllers OEM Manual (Preliminary)'
        ];
        $this->assertEquals($data, $expected);
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
        $this->_db->expects($this->once())->method('getCompanyIdForSiteDirectory')->with('bitsavers', 'microdata', '')->willReturn($companyId);
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
            'site_company_parent_directory' => '',
            'pubs' => [],
            'exists' => true,
            'pub_id' => $pubId,
            'keywords' => $part . ' ' . $title
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
        $this->_db->expects($this->once())->method('getCompanyIdForSiteDirectory')->with('bitsavers', 'microdata', '')->willReturn($companyId);
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
            'site_company_parent_directory' => '',
            'pubs' => [],
            'exists' => true,
            'pub_id' => null,
            'keywords' => $part
        ];
        $this->assertEquals($expectedData, $data);
    }

    private static function sitesResultsForVtda()
    {
        return [self::vtdaSiteRow()];
    }

    private static function vtdaSiteRow()
    {
        return self::databaseRowFromDictionary([
            'site_id' => '58',
            'name' => 'VTDA',
            'url' => 'http://vtda.org/',
            'description' => "The Vintage Technology Digital Archive",
            'copy_base' => 'http://vtda.org/docs/',
            'low' => 'N',
            'live' => 'Y',
            'display_order' => '999'
        ]);
    }

    private static function databaseRowFromDictionary(array $dict)
    {
        $result = array();
        $i = 0;
        foreach ($dict as $key => $value)
        {
            $result[$key] = $value;
            $result[$i] = $value;
            $i++;
        }
        return $result;
    }

    private static function sitesResultsForBitSavers($id)
    {
        return [self::bitSaversSiteRow($id)];
    }

    private static function bitSaversSiteRow($siteId)
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
