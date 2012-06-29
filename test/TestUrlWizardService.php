<?php

require_once 'test/FakeManxDatabase.php';
require_once 'test/FakeManx.php';
require_once 'test/FakeUser.php';
require_once 'UrlWizardService.php';

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
	private $_manx;

	public function testUrlComponentsMatchBitSaversOrg()
	{
		$components = parse_url('http://bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf');
		$siteComponents = parse_url('http://bitsavers.org/pdf/');
		$this->assertTrue(UrlWizardService::urlComponentsMatch($components, $siteComponents));
	}

	public function testUrlComponentsMatchWwwBitSaversOrg()
	{
		$components = parse_url('http://www.bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf');
		$siteComponents = parse_url('http://bitsavers.org/pdf/');
		$this->assertTrue(UrlWizardService::urlComponentsMatch($components, $siteComponents));
	}

	public function testExtractPubDateSeparateMonthYear()
	{
		$this->assertEquals('1975-03', UrlWizardService::extractPubDate('foo_bar_Mar_1975'));
	}

	public function testExtractPubDateMonthYear()
	{
		$this->assertEquals('1975-03', UrlWizardService::extractPubDate('foo_bar_Mar1975'));
	}

	public function testExtractPubDateYear()
	{
		$this->assertEquals('1975', UrlWizardService::extractPubDate('foo_bar_1975'));
	}

	public function testExtractPubDateTwoDigitYear()
	{
		$this->assertEquals('1975', UrlWizardService::extractPubDate('foo_bar_75'));
	}

	public function testExtractPubDateSeparateMonthTwoDigitYear()
	{
		$this->assertEquals('1975-03', UrlWizardService::extractPubDate('foo_bar_Mar_75'));
	}

	public function testExtractPubDateMonthTwoDigitYear()
	{
		$this->assertEquals('1975-03', UrlWizardService::extractPubDate('foo_bar_Mar75'));
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

	public function testProcessRequestNewBitSaversCompany()
	{
		$this->_manx = new FakeManx();
		$db = new FakeManxDatabase();
		$this->_manx->getDatabaseFakeResult = $db;
		$this->_manx->getUserFromSessionFakeResult = new FakeUser();
		$db->getSitesFakeResult =
			array(
				array(
					'siteid' => '3',
					'0' => '3',
					'name' => 'bitsavers',
					'1' => 'bitsavers',
					'url' => 'http://bitsavers.org/',
					'2' => 'http://bitsavers.org/',
					'description' => "Al Kossow's Bitsavers",
					'3' => "Al Kossow's Bitsavers",
					'copy_base' => 'http://bitsavers.org/pdf/',
					'4' => 'http://bitsavers.org/pdf/',
					'low' => 'N',
					'5' => 'N',
					'live' => 'Y',
					'6' => 'Y',
					'display_order' => '999',
					'7' => '999'
				)
			);
		$db->getMirrorsFakeResult = array();
		$db->getCompanyForBitSaversDirectoryFakeResult = '-1';
		$db->getFormatForExtensionFakeResult = 'PDF';
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$vars = array(
			'method' => 'url-lookup',
			'url' => 'http://bitsavers.org/pdf/sandersAssociates/graphic7/Graphic_7_Monitor_Preliminary_Users_Guide_May_1979.pdf'
			);
		ob_start();
		$page = new UrlWizardServiceTester($this->_manx, $vars);

		$page->processRequest();

		$output = ob_get_contents();
		ob_end_clean();
		$expected = json_encode(array(
			'url' => 'http://bitsavers.org/pdf/sandersAssociates/graphic7/Graphic_7_Monitor_Preliminary_Users_Guide_May_1979.pdf',
			'site' => array('siteid' => '3', '0' => '3',
				'name' => 'bitsavers', '1' => 'bitsavers',
				'url' => 'http://bitsavers.org/', '2' => 'http://bitsavers.org/',
				'description' => "Al Kossow's Bitsavers", '3' => "Al Kossow's Bitsavers",
				'copy_base' => 'http://bitsavers.org/pdf/', '4' => 'http://bitsavers.org/pdf/',
				'low' => 'N', '5' => 'N',
				'live' => 'Y', '6' => 'Y',
				'display_order' => '999', '7' => '999'),
			'company' => '-1',
			'part' => '',
			'pub_date' => '1979-05',
			'title' => 'Graphic 7 Monitor Preliminary Users Guide',
			'format' => 'PDF',
			'bitsavers_directory' => 'sandersAssociates',
			'pubs' => array()
			));
		$this->assertEquals($expected, $output);
		$this->assertTrue($db->getSitesCalled);
		$this->assertTrue($db->getCompanyForBitSaversDirectoryCalled);
		$this->assertTrue($db->getFormatForExtensionCalled);
	}

	public function testProcessRequestUrlLookup()
	{
		$this->_manx = new FakeManx();
		$db = new FakeManxDatabase();
		$this->_manx->getDatabaseFakeResult = $db;
		$this->_manx->getUserFromSessionFakeResult = new FakeUser();
		$db->getSitesFakeResult =
			array(
				array(
					'siteid' => '3',
					'0' => '3',
					'name' => 'bitsavers',
					'1' => 'bitsavers',
					'url' => 'http://bitsavers.org/',
					'2' => 'http://bitsavers.org/',
					'description' => "Al Kossow's Bitsavers",
					'3' => "Al Kossow's Bitsavers",
					'copy_base' => 'http://bitsavers.org/pdf/',
					'4' => 'http://bitsavers.org/pdf/',
					'low' => 'N',
					'5' => 'N',
					'live' => 'Y',
					'6' => 'Y',
					'display_order' => '999',
					'7' => '999'
				)
			);
		$db->getMirrorsFakeResult =
			array(
				array(
					'mirror_id' => '2',
					'0' => '2',
					'site' => '3',
					'1' => '3',
					'original_stem' => 'http://bitsavers.org/',
					'2' => 'http://bitsavers.org/',
					'copy_stem' => 'http://bitsavers.trailing-edge.com/',
					'3' => 'http://bitsavers.trailing-edge.com/',
					'rank' => '9',
					'4' => '9'
				)
			);
		$db->getCompanyForBitSaversDirectoryFakeResult = '5';
		$db->getFormatForExtensionFakeResult = 'PDF';
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$vars = array(
			'method' => 'url-lookup',
			'url' => 'http%3A%2F%2Fbitsavers.trailing-edge.com%2Fpdf%2Ftektronix%2F401x%2F070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf');
		ob_start();
		$page = new UrlWizardServiceTester($this->_manx, $vars);

		$page->processRequest();

		$output = ob_get_contents();
		ob_end_clean();
		$expected = json_encode(array(
			"url" => "http://bitsavers.org/pdf/tektronix/401x/070-1183-01_Rev_B_4010_Maintenance_Manual_Apr_1976.pdf",
			"site" => array("siteid" => "3",
				"0" => "3",
				"name" => "bitsavers",
				"1" => "bitsavers",
				"url" => "http://bitsavers.org/",
				"2" => "http://bitsavers.org/",
				"description" => "Al Kossow's Bitsavers",
				"3" => "Al Kossow's Bitsavers",
				"copy_base" => "http://bitsavers.org/pdf/",
				"4" => "http://bitsavers.org/pdf/",
				"low" => "N",
				"5" => "N",
				"live" => "Y",
				"6" => "Y",
				"display_order" => "999",
				"7" => "999"),
			"company" => "5",
			"part" => "070-1183-01",
			"pub_date" => "1976-04",
			"title" => "Rev B 4010 Maintenance Manual",
			"format" => "PDF",
			"bitsavers_directory" => "tektronix",
			"pubs" => array()
		));
		$this->assertTrue($db->getSitesCalled);
		$this->assertTrue($db->getMirrorsCalled);
		$this->assertTrue($db->getCompanyForBitSaversDirectoryCalled);
		$this->assertTrue($db->getFormatForExtensionCalled);
		$this->assertEquals($expected, $output);
	}

	public function testWwwBitSaversOrgProcessRequestUrlLookup()
	{
		$this->_manx = new FakeManx();
		$db = new FakeManxDatabase();
		$this->_manx->getDatabaseFakeResult = $db;
		$this->_manx->getUserFromSessionFakeResult = new FakeUser();
		$db->getSitesFakeResult =
			array(
				array(
					'siteid' => '3',
					'0' => '3',
					'name' => 'bitsavers',
					'1' => 'bitsavers',
					'url' => 'http://bitsavers.org/',
					'2' => 'http://bitsavers.org/',
					'description' => "Al Kossow's Bitsavers",
					'3' => "Al Kossow's Bitsavers",
					'copy_base' => 'http://bitsavers.org/pdf/',
					'4' => 'http://bitsavers.org/pdf/',
					'low' => 'N',
					'5' => 'N',
					'live' => 'Y',
					'6' => 'Y',
					'display_order' => '999',
					'7' => '999'
				)
			);
		$db->getMirrorsFakeResult = array();
		$db->getCompanyForBitSaversDirectoryFakeResult = '-1';
		$db->getFormatForExtensionFakeResult = 'PDF';
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$vars = array(
			'method' => 'url-lookup',
			'url' => 'http%3A%2F%2Fwww.bitsavers.org%2Fpdf%2Funivac%2F1100%2FUE-637_1108execUG_1970.pdf'
			);
		ob_start();
		$page = new UrlWizardServiceTester($this->_manx, $vars);

		$page->processRequest();

		$output = ob_get_contents();
		ob_end_clean();
		$expected = json_encode(array(
			"url" => 'http://bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf',
			"site" => array("siteid" => "3",
				"0" => "3",
				"name" => "bitsavers",
				"1" => "bitsavers",
				"url" => "http://bitsavers.org/",
				"2" => "http://bitsavers.org/",
				"description" => "Al Kossow's Bitsavers",
				"3" => "Al Kossow's Bitsavers",
				"copy_base" => "http://bitsavers.org/pdf/",
				"4" => "http://bitsavers.org/pdf/",
				"low" => "N",
				"5" => "N",
				"live" => "Y",
				"6" => "Y",
				"display_order" => "999",
				"7" => "999"),
			"company" => "-1",
			"part" => "UE-637",
			"pub_date" => "1970",
			"title" => "1108execUG",
			"format" => "PDF",
			"bitsavers_directory" => "univac",
			"pubs" => array()
		));
		$this->assertTrue($db->getSitesCalled);
		$this->assertTrue($db->getCompanyForBitSaversDirectoryCalled);
		$this->assertEquals('univac', $db->getCompanyForBitSaversDirectoryLastDir);
		$this->assertTrue($db->getFormatForExtensionCalled);
		$this->assertEquals($expected, $output);
	}
}

?>
