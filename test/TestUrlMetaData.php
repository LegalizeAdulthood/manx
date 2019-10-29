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
        $this->assertTrue(is_object($this->_meta));
        $this->assertFalse(is_null($this->_meta));
    }

    public function testDetermineDataForBitSaversNoCopy()
    {
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->willReturn($this->_urlInfo);
        $copySize = 4096;
        $this->_urlInfo->expects($this->once())->method('size')->willReturn($copySize);
        $siteId = 3;
        $bitsaversSiteRow = [
                'site_id' => $siteId,
                'name' => 'bitsavers',
                'url' => 'http://bitsavers.org',
                'description' => '',
                'copy_base' => 'http://bitsavers.org/pdf/',
                'low' => 'N',
                'live' => 'Y',
                'display_order' => 1
            ];
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
        $bitsaversSiteRow = [
                'site_id' => $siteId,
                'name' => 'bitsavers',
                'url' => 'http://bitsavers.org',
                'description' => '',
                'copy_base' => 'http://bitsavers.org/pdf/',
                'low' => 'N',
                'live' => 'Y',
                'display_order' => 1
            ];
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

    public function testUrlComponentsMatchBitSaversOrg()
    {
        $this->assertUrlMatchesSite(
            'http://bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf',
            'http://bitsavers.org/pdf/');
    }

    public function testUrlComponentsMatchWwwBitSaversOrg()
    {
        $this->assertUrlMatchesSite(
            'http://www.bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf',
            'http://bitsavers.org/pdf/');
    }

    private function assertUrlMatchesSite($url, $site)
    {
        $this->assertTrue(UrlMetaData::urlComponentsMatch(parse_url($url), parse_url($site)));
    }

    public function testExtractPubDateSingleTrailingDigit()
    {
        list($date, $newFileBase) = UrlWizardService::extractPubDate('foo_bar_3');
        $this->assertEquals('', $date);
        $this->assertEquals('foo_bar_3', $newFileBase);
    }

    public function testExtractPubDateSeparateMonthYear()
    {
        $this->assertPubDateForFileBase('1975-03', 'foo_bar_Mar_1975');
    }

    public function testExtractPubDateMonthYear()
    {
        $this->assertPubDateForFileBase('1975-03', 'foo_bar_Mar1975');
    }

    public function testExtractPubDateYear()
    {
        $this->assertPubDateForFileBase('1975', 'foo_bar_1975');
    }

    public function testExtractPubDateTwoDigitYear()
    {
        $this->assertPubDateForFileBase('1975', 'foo_bar_75');
    }

    public function testExtractPubDateSeparateMonthTwoDigitYear()
    {
        $this->assertPubDateForFileBase('1975-03', 'foo_bar_Mar_75');
    }

    public function testExtractPubDateMonthTwoDigitYear()
    {
        $this->assertPubDateForFileBase('1975-03', 'foo_bar_Mar75');
    }

    private function assertPubDateForFileBase($pubDate, $fileBase)
    {
        list($date, $newFileBase) = UrlWizardService::extractPubDate($fileBase);
        $this->assertEquals($pubDate, $date);
        $this->assertEquals('foo_bar', $newFileBase);
    }

    public function testTitleForBaseWithPoundSign()
    {
        $this->assertTitleForFileBase('Micro Cornucopia #50', 'Micro_Cornucopia_%2350');
    }

    public function testTitleForFileBaseWithUnderscores()
    {
        $this->assertTitleForFileBase('Foo Bar Gronky', 'Foo_Bar_Gronky');
    }

    public function testTitleForFileBaseWithSpaces()
    {
        $this->assertTitleForFileBase('Foo Bar Gronky', 'Foo_Bar Gronky');
    }

    public function testTitleForFileBaseWithMixedCase()
    {
        $this->assertTitleForFileBase('Foo Bar Gronky', 'FooBarGronky');
    }

    public function testTitleMixedCaseAndUnderscores()
    {
        $this->assertTitleForFileBase('TI CBL Real World Math Guidebook', 'TI_CBL_RealWorldMath_Guidebook');
    }

    public function testTitleForFileBaseWithMixedCaseTwoWords()
    {
        $this->assertTitleForFileBase('Foo Bar', 'FooBar');
    }

    public function testTitleForFileBaseWithMixedCaseFourWords()
    {
        $this->assertTitleForFileBase('Foo Bar Blobby Phlegm', 'FooBarBlobbyPhlegm');
    }

    private function assertTitleForFileBase($title, $fileBase)
    {
        $this->assertEquals($title, UrlMetaData::titleForFileBase($fileBase));
    }

    private function createPublicationsForCompare($leftPart, $leftRev, $leftTitle, $rightPart, $rightRev, $rightTitle)
    {
        $columns = array('ph_pub', 'ph_part', 'ph_revision', 'ph_title');
        $left = DatabaseTester::createResultRowsForColumns($columns,
            array(array('1', $leftPart, $leftRev, $leftTitle)));
        $left = $left[0];
        $right = DatabaseTester::createResultRowsForColumns($columns,
            array(array('2', $rightPart, $rightRev, $rightTitle)));
        $right = $right[0];
        return array($left, $right);
    }

    private $_manx;

}
