<?php

require_once 'test/FakeManxDatabase.php';
require_once 'test/FakeManx.php';
require_once 'test/FakeUser.php';
require_once 'pages/UrlWizardService.php';

class UrlWizardServiceTester extends UrlWizardService
{
    public function renderBodyContent()
    {
        parent::renderBodyContent();
    }

    protected function redirect($target)
    {
        $this->redirectCalled = true;
        $this->redirectLastTarget = $target;
    }
    public $redirectCalled, $redirectLastTarget;

    public function postPage()
    {
        parent::postPage();
    }

    protected function header($field)
    {
        $this->headerCalled = true;
        $this->headerLastField = $field;
    }
    public $headerCalled, $headerLastField;
}

class TestUrlWizardService extends PHPUnit_Framework_TestCase
{
    protected $_manx;

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
        $this->assertTrue(UrlWizardService::urlComponentsMatch(parse_url($url), parse_url($site)));
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

    public function testConstruct()
    {
        $this->_manx = new FakeManx();
        $_SERVER['PATH_INFO'] = '';
        $vars = array();
        $page = new UrlWizardServiceTester($this->_manx, $vars);
        $this->assertTrue(is_object($page));
        $this->assertFalse(is_null($page));
    }

    public function testComparePublicationsByTitle()
    {
        list($left, $right) = $this->createPublicationsForCompare('', '', 'foo', '', '', 'bar');
        $this->assertEquals(1, UrlWizardService::comparePublications($left, $right));
    }

    public function testComparePublicationsByPart()
    {
        list($left, $right) = $this->createPublicationsForCompare('00', '', 'foo', '01', '', 'bar');
        $this->assertEquals(-1, UrlWizardService::comparePublications($left, $right));
    }

    public function testComparePublicationsByRev()
    {
        list($left, $right) = $this->createPublicationsForCompare('00', 'B', 'foo', '00', 'A', 'foo');
        $this->assertEquals(1, UrlWizardService::comparePublications($left, $right));
    }

    private function createPublicationsForCompare($leftPart, $leftRev, $leftTitle, $rightPart, $rightRev, $rightTitle)
    {
        $columns = array('ph_pub', 'ph_part', 'ph_revision', 'ph_title');
        $left = FakeDatabase::createResultRowsForColumns($columns,
            array(array('1', $leftPart, $leftRev, $leftTitle)));
        $left = $left[0];
        $right = FakeDatabase::createResultRowsForColumns($columns,
            array(array('2', $rightPart, $rightRev, $rightTitle)));
        $right = $right[0];
        return array($left, $right);
    }
}

class TestUrlWizardServiceProcessRequest extends TestUrlWizardService
{
    private $_db;

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
        return array(self::bitsaversSiteRow());
    }

    private static function bitsaversSiteRow()
    {
        return self::databaseRowFromDictionary(
            array(
                'siteid' => '3',
                'name' => 'bitsavers',
                'url' => 'http://bitsavers.org/',
                'description' => "Al Kossow's Bitsavers",
                'copy_base' => 'http://bitsavers.org/pdf/',
                'low' => 'N',
                'live' => 'Y',
                'display_order' => '999'
            ));
    }

    protected function setUp()
    {
        $this->_manx = new FakeManx();
        $this->_db = new FakeManxDatabase();
        $this->_manx->getDatabaseFakeResult = $this->_db;
        $this->_manx->getUserFromSessionFakeResult = new FakeUser();
        $this->_db->getSitesFakeResult = self::sitesResultsForBitSavers();
        $this->_db->getFormatForExtensionFakeResult = 'PDF';
        $_SERVER['PATH_INFO'] = '';
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    public function testProcessRequestNewBitSaversCompany()
    {
        $this->_db->getMirrorsFakeResult = array();
        $this->_db->getCompanyForBitSaversDirectoryFakeResult = '-1';
        $url = 'http://bitsavers.org/pdf/sandersAssociates/graphic7/Graphic_7_Monitor_Preliminary_Users_Guide_May_1979.pdf';
        $vars = self::varsForUrl($url);
        ob_start();
        $page = new UrlWizardServiceTester($this->_manx, $vars);

        $page->processRequest();

        $output = ob_get_contents();
        ob_end_clean();
        $expected = json_encode(array(
            'url' => $url,
            'site' => self::bitsaversSiteRow(),
            'company' => '-1',
            'part' => '',
            'pub_date' => '1979-05',
            'title' => 'Graphic 7 Monitor Preliminary Users Guide',
            'format' => 'PDF',
            'bitsavers_directory' => 'sandersAssociates',
            'pubs' => array()
            ));
        $this->assertEquals($expected, $output);
        $this->assertTrue($this->_db->getSitesCalled);
        $this->assertTrue($this->_db->getCompanyForBitSaversDirectoryCalled);
        $this->assertTrue($this->_db->getFormatForExtensionCalled);
    }

    private static function varsForUrl($url)
    {
        return array(
            'method' => 'url-lookup',
            'url' => $url
        );
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
        $this->_db->getCompanyForBitSaversDirectoryFakeResult = '5';
        $urlBase = '/pdf/tektronix/401x/070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf';
        $vars = self::varsForUrl('http://bitsavers.trailing-edge.com' + $urlBase);
        ob_start();
        $page = new UrlWizardServiceTester($this->_manx, $vars);

        $page->processRequest();

        $output = ob_get_contents();
        ob_end_clean();
        $expected = json_encode(array(
            "url" => "http://bitsavers.org" + $urlBase,
            "site" => self::bitsaversSiteRow(),
            "company" => "5",
            "part" => "070-1183-01",
            "pub_date" => "1976-04",
            "title" => "Rev B 4010 Maintenance Manual",
            "format" => "PDF",
            "bitsavers_directory" => "tektronix",
            "pubs" => array()
        ));
        $this->assertTrue($this->_db->getSitesCalled);
        $this->assertTrue($this->_db->getMirrorsCalled);
        $this->assertTrue($this->_db->getCompanyForBitSaversDirectoryCalled);
        $this->assertTrue($this->_db->getFormatForExtensionCalled);
        $this->assertEquals($expected, $output);
    }

    public function testWwwBitSaversOrgProcessRequestUrlLookup()
    {
        $this->_db->getMirrorsFakeResult = array();
        $this->_db->getCompanyForBitSaversDirectoryFakeResult = '-1';
        $urlBase = '/pdf/univac/1100/UE-637_1108execUG_1970.pdf';
        $vars = self::varsForUrl('http://www.bitsavers.org' + $urlBase);
        ob_start();
        $page = new UrlWizardServiceTester($this->_manx, $vars);

        $page->processRequest();

        $output = ob_get_contents();
        ob_end_clean();
        $expected = json_encode(array(
            "url" => 'http://bitsavers.org' + $urlBase,
            "site" => self::bitsaversSiteRow(),
            "company" => "-1",
            "part" => "UE-637",
            "pub_date" => "1970",
            "title" => "1108execUG",
            "format" => "PDF",
            "bitsavers_directory" => "univac",
            "pubs" => array()
        ));
        $this->assertTrue($this->_db->getSitesCalled);
        $this->assertTrue($this->_db->getCompanyForBitSaversDirectoryCalled);
        $this->assertEquals('univac', $this->_db->getCompanyForBitSaversDirectoryLastDir);
        $this->assertTrue($this->_db->getFormatForExtensionCalled);
        $this->assertEquals($expected, $output);
    }
}
