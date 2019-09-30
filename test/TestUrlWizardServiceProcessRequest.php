<?php

require_once 'test/FakeManxDatabase.php';
require_once 'test/FakeManx.php';
require_once 'test/UrlWizardServiceTester.php';

class TestUrlWizardServiceProcessRequest extends PHPUnit\Framework\TestCase
{
    private $_db;
    private $_manx;
    private $_urlInfoFactory;
    private $_urlInfo;

    protected function setUp()
    {
        $this->_manx = new FakeManx();
        $this->_db = new FakeManxDatabase();
        $this->_manx->getDatabaseFakeResult = $this->_db;
        $user = $this->createMock(IUser::class);
        $user->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->_manx->getUserFromSessionFakeResult = $user;
        $this->_db->getSitesFakeResult = self::sitesResultsForBitSavers();
        $this->_db->getFormatForExtensionFakeResult = 'PDF';
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->_urlInfo = $this->createMock(IUrlInfo::class);
        $this->_urlInfoFactory = $this->createMock(IUrlInfoFactory::class);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')->willReturn($this->_urlInfo);
    }

    public function testProcessRequestNonExistentUrl()
    {
        $this->_db->getMirrorsFakeResult = array();
        $this->_db->getCompanyForSiteDirectoryFakeResult = '-1';
        $url = 'http://bitsavers.org/pdf/sandersAssociates/graphic7/Graphic_7_Monitor_Preliminary_Users_Guide_May_1979.pdf';
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(false);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with($this->equalTo($url))->willReturn($this->_urlInfo);
        $vars = self::varsForUrlLookup($url);
        $page = new UrlWizardServiceTester($this->_manx, $vars, $this->_urlInfoFactory);

        ob_start();
        $page->processRequest();
        $output = ob_get_contents();
        ob_end_clean();

        $expected = json_encode(array('valid' => false));
        $this->assertEquals($expected, $output);
    }

    public function testProcessRequestNewBitSaversCompany()
    {
        $this->_db->getMirrorsFakeResult = array();
        $this->_db->getCompanyForSiteDirectoryFakeResult = '-1';
        $url = 'http://bitsavers.org/pdf/sandersAssociates/graphic7/Graphic_7_Monitor_Preliminary_Users_Guide_May_1979.pdf';
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with($this->equalTo($url))->willReturn($this->_urlInfo);
        $vars = self::varsForUrlLookup($url);
        $page = new UrlWizardServiceTester($this->_manx, $vars, $this->_urlInfoFactory);

        ob_start();
        $page->processRequest();
        $output = ob_get_contents();
        ob_end_clean();

        $expected = json_encode(array(
            'url' => $url,
            'mirror_url' => '',
            'size' => 1266,
            'valid' => true,
            'site' => self::bitSaversSiteRow(),
            'company' => '-1',
            'part' => '',
            'pub_date' => '1979-05',
            'title' => 'Graphic 7 Monitor Preliminary Users Guide',
            'format' => 'PDF',
            'site_company_directory' => 'sandersAssociates',
            'pubs' => array()
            ));
        $this->assertEquals($expected, $output);
        $this->assertTrue($this->_db->getSitesCalled);
        $this->assertTrue($this->_db->getCompanyForSiteDirectoryCalled);
        $this->assertTrue($this->_db->getFormatForExtensionCalled);
    }

    public function testProcessRequestUrlLookup()
    {
        $this->_db->getMirrorsFakeResult =
            array(self::databaseRowFromDictionary(array(
                'mirror_id' => '2',
                'site' => '3',
                'original_stem' => 'http://bitsavers.org/',
                'copy_stem' => 'http://bitsavers.trailing-edge.com/',
                'rank' => '9'
                )
            ));
        $this->_db->getCompanyForSiteDirectoryFakeResult = '5';
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with($this->equalTo('http://bitsavers.trailing-edge.com/pdf/tektronix/401x/070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf'))
            ->willReturn($this->_urlInfo);
        $urlBase = '/pdf/tektronix/401x/070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf';
        $vars = self::varsForUrlLookup('http://bitsavers.trailing-edge.com' . $urlBase);
        $page = new UrlWizardServiceTester($this->_manx, $vars, $this->_urlInfoFactory);

        ob_start();
        $page->processRequest();
        $output = ob_get_contents();
        ob_end_clean();

        $expected = json_encode(array(
            'url' => 'http://bitsavers.org' . $urlBase,
            'mirror_url' => 'http://bitsavers.trailing-edge.com' . $urlBase,
            'size' => 1266,
            'valid' => true,
            'site' => self::bitSaversSiteRow(),
            'company' => '5',
            'part' => '070-1183-01',
            'pub_date' => '1976-04',
            'title' => 'Rev B 4010 Maintenance Manual',
            'format' => 'PDF',
            'site_company_directory' => 'tektronix',
            'pubs' => array()
        ));
        $this->assertEquals($expected, $output);
        $this->assertTrue($this->_db->getMirrorsCalled);
        $this->assertTrue($this->_db->getCompanyForSiteDirectoryCalled);
        $this->assertEquals('tektronix', $this->_db->getCompanyForSiteDirectoryLastDir);
    }

    public function testWwwBitSaversOrgProcessRequestUrlLookup()
    {
        $this->_db->getMirrorsFakeResult = array();
        $this->_db->getCompanyForSiteDirectoryFakeResult = '-1';
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with($this->equalTo('http://www.bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf'))
            ->willReturn($this->_urlInfo);
        $urlBase = '/pdf/univac/1100/UE-637_1108execUG_1970.pdf';
        $vars = self::varsForUrlLookup('http://www.bitsavers.org' . $urlBase);
        $page = new UrlWizardServiceTester($this->_manx, $vars, $this->_urlInfoFactory);

        ob_start();
        $page->processRequest();
        $output = ob_get_contents();
        ob_end_clean();

        $expected = json_encode(array(
            'url' => 'http://bitsavers.org' . $urlBase,
            'mirror_url' => '',
            'size' => 1266,
            'valid' => true,
            'site' => self::bitsaversSiteRow(),
            'company' => '-1',
            'part' => 'UE-637',
            'pub_date' => '1970',
            'title' => '1108exec UG',
            'format' => 'PDF',
            'site_company_directory' => 'univac',
            'pubs' => array()
        ));
        $this->assertEquals($expected, $output);
        $this->assertEquals('univac', $this->_db->getCompanyForSiteDirectoryLastDir);
    }

    public function testChiClassicCompUrlLookup()
    {
        $this->_db->getSitesFakeResult = self::sitesResultsForChiClassicComp();
        $this->_db->getMirrorsFakeResult = array();
        $this->_db->getCompanyForSiteDirectoryFakeResult = '66';
        $this->_urlInfo->expects($this->once())->method('size')->willReturn(1266);
        $this->_urlInfoFactory->expects($this->once())->method('createUrlInfo')
            ->with($this->equalTo('http://chiclassiccomp.org/docs/content/computing/Motorola/6064A-5M-668_MDR-1000Brochure.pdf'))
            ->wilLReturn($this->_urlInfo);
        $urlBase = '/docs/content/computing/Motorola/6064A-5M-668_MDR-1000Brochure.pdf';
        $vars = self::varsForUrlLookup('http://chiclassiccomp.org' . $urlBase);
        $page = new UrlWizardServiceTester($this->_manx, $vars, $this->_urlInfoFactory);

        ob_start();
        $page->processRequest();
        $output = ob_get_contents();
        ob_end_clean();

        $expected = json_encode(array(
            'url' => 'http://chiclassiccomp.org' . $urlBase,
            'mirror_url' => '',
            'size' => 1266,
            'valid' => true,
            'site' => self::chiClassicCompSiteRow(),
            'company' => '66',
            'part' => '6064A-5M-668',
            'pub_date' => '',
            'title' => 'MDR-1000Brochure',
            'format' => 'PDF',
            'site_company_directory' => 'Motorola',
            'pubs' => array()
        ));
        $this->assertEquals($expected, $output);
        $this->assertTrue($this->_db->getSitesCalled);
        $this->assertTrue($this->_db->getFormatForExtensionCalled);
        $this->assertTrue($this->_db->getCompanyForSiteDirectoryCalled);
        $this->assertEquals('Motorola', $this->_db->getCompanyForSiteDirectoryLastDir);
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

    private static function sitesResultsForBitSavers()
    {
        return array(self::bitSaversSiteRow());
    }

    private static function sitesResultsForChiClassicComp()
    {
        return array(self::chiClassicCompSiteRow());
    }

    private static function bitSaversSiteRow()
    {
        return self::databaseRowFromDictionary(
            array(
                'site_id' => '3',
                'name' => 'bitsavers',
                'url' => 'http://bitsavers.org/',
                'description' => "Al Kossow's Bitsavers",
                'copy_base' => 'http://bitsavers.org/pdf/',
                'low' => 'N',
                'live' => 'Y',
                'display_order' => '999'
            ));
    }

    private static function chiClassicCompSiteRow()
    {
        return self::databaseRowFromDictionary(
            array(
                'site_id' => '58',
                'name' => 'ChiClassicComp',
                'url' => 'http://chiclassiccomp.org/',
                'description' => "Chicago Classic Computing's document archive",
                'copy_base' => 'http://chiclassiccomp.org/docs/content/',
                'low' => 'N',
                'live' => 'Y',
                'display_order' => '999'
            ));
    }

    private static function varsForUrlLookup($url)
    {
        return array(
            'method' => 'url-lookup',
            'url' => $url
        );
    }
}
