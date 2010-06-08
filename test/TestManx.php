<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'Manx.php';
	require_once 'test/FakeDatabase.php';
	require_once 'test/FakeStatement.php';
	
	class ManxRenderDetailsTester extends Manx
	{
		public function __construct($db)
		{
			Manx::__construct($db);
			$this->renderLanguageCalled = false;
			$this->renderAmendmentsCalled = false;
			$this->renderOSTagsCalled = false;
			$this->renderLongDecriptionCalled = false;
			$this->renderCitationsCalled = false;
			$this->renderSupersessionsCalled = false;
			$this->renderTableOfContentsCalled = false;
			$this->renderCopiesCalled = false;
		}
		
		public $renderLanguageCalled, $renderLanguageLastLanguage;
		public function renderLanguage($lang)
		{
			$this->renderLanguageCalled = true;
			$this->renderLanguageLastLanguage = $lang;
		}
		
		public $renderAmendmentsCalled, $renderAmendmentsLastPubId;
		public function renderAmendments($pubId)
		{
			$this->renderAmendmentsCalled = true;
			$this->renderAmendmentsLastPubId = $pubId;
		}
		
		public $renderOSTagsCalled, $renderOSTagsLastPubId;
		public function renderOSTags($pubId)
		{
			$this->renderOSTagsCalled = true;
			$this->renderOSTagsLastPubId = $pubId;
		}
		
		public $renderLongDescriptionCalled, $renderLongDescriptionLastPubId;
		public function renderLongDescription($pubId)
		{
			$this->renderLongDescriptionCalled = true;
			$this->renderLongDescriptionLastPubId = $pubId;
		}
		
		public $renderCitationsCalled, $renderCitationsLastPubId;
		public function renderCitations($pubId)
		{
			$this->renderCitationsCalled = true;
			$this->renderCitationsLastPubId = $pubId;
		}
		
		public $renderSupersessionsCalled, $renderSupersessionsLastPubId;
		public function renderSupersessions($pubId)
		{
			$this->renderSupersessionsCalled = true;
			$this->renderSupersessionsLastPubId = $pubId;
		}
		
		public $renderTableOfContentsCalled, $renderTableOfContentsLastPubId;
		public function renderTableOfContents($pubId)
		{
			$this->renderTableOfContentsCalled = true;
			$this->renderTableOfContentsLastPubId = $pubId;
		}
		
		public $renderCopiesCalled, $renderCopiesLastPubId;
		public function renderCopies($pubId)
		{
			$this->renderCopiesCalled = true;
			$this->renderCopiesLastPubId = $pubId;
		}
	}
	
	class TestManx extends PHPUnit_Framework_TestCase
	{
		private function fakeStatementFetchResults($results)
		{
			$stmt = new FakeStatement();
			$stmt->fetchFakeResult = $results;
			return $stmt;
		}
		
		public function testRenderDocumentSummary()
		{
			$db = new FakeDatabase();
			$db->queryFakeResultsForQuery = array(
				"SELECT COUNT(*) FROM `PUB`" => $this->fakeStatementFetchResults(array(12)),
				"SELECT COUNT(DISTINCT `pub`) FROM `COPY`" => $this->fakeStatementFetchResults(array(24)),
				"SELECT COUNT(*) FROM `SITE`" => $this->fakeStatementFetchResults(array(43))
				);
			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderDocumentSummary();
			$this->assertTrue($db->queryCalled);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals("12 manuals, 24 of which are online, at 43 websites", $output);
		}
		
		public function testRenderCompanyList()
		{
			$db = new FakeDatabase();
			$db->queryFakeResultsForQuery = array(
				"SELECT COUNT(*) FROM `COMPANY` WHERE `display` = 'Y'" => $this->fakeStatementFetchResults(array(2)),
				"SELECT `id`,`name` FROM `COMPANY` WHERE `display` = 'Y' ORDER BY `sort_name`" =>
					array(array('id' => 1, 'name' => "DEC"),
						array('id' => 2, 'name' => "HP"))
				);
			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderCompanyList();
			$this->assertTrue($db->queryCalled);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<a href="search.php?cp=1">DEC</a>, <a href="search.php?cp=2">HP</a>', $output);
		}
		
		public function testRenderSiteList()
		{
			$db = new FakeDatabase();
			$db->queryFakeResults = array(
					array('url' => 'http://www.dec.com', 'description' => 'DEC', 'low' => false),
					array('url' => 'http://www.hp.com', 'description' => 'HP', 'low' => true)
				);
			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderSiteList();
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertTrue($db->queryCalled);
			$this->assertEquals("SELECT `url`,`description`,`low` FROM `SITE` WHERE `live`='Y' ORDER BY `siteid`", $db->queryLastStatement);
			$this->assertEquals('<ul><li><a href="http://www.dec.com">DEC</a></li>'
				. '<li><a href="http://www.hp.com">HP</a> <span class="warning">(Low Bandwidth)</span></li></ul>', $output);
		}
		
		public function testDetailParamsForPathInfoCompanyAndId()
		{
			$params = Manx::detailParamsForPathInfo('/1,2');
			$this->assertEquals(4, count(array_keys($params)));
			$this->assertEquals(1, $params['cp']);
			$this->assertEquals(2, $params['id']);
			$this->assertEquals(1, $params['cn']);
			$this->assertEquals(0, $params['pn']);
		}
		
		public function testNeatListPlainOneItem()
		{
			$this->assertEquals('English', Manx::neatListPlain(array('English')));
		}
		
		public function testNeatListPlainTwoItems()
		{
			$this->assertEquals('English and French', Manx::neatListPlain(array('English', 'French')));
		}
		
		public function testNeatListPlainThreeItems()
		{
			$this->assertEquals('English, French and German', Manx::neatListPlain(array('English', 'French', 'German')));
		}
		
		public function testRenderLanguageEnglishGivesNoOutput()
		{
			$db = new FakeDatabase();
			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderLanguage('+en');
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('', $output);
		}
		
		public function testRenderLanguageFrench()
		{
			$db = new FakeDatabase();
			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderLanguage('+fr');
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals("<tr><td>Language:</td><td>French</td></tr>\n", $output);
		}
		
		public function testRenderLanguageEnglishFrenchGerman()
		{
			$db = new FakeDatabase();
			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderLanguage('+en+fr+de');
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals("<tr><td>Languages:</td><td>English, French and German</td></tr>\n", $output);
		}
		
		public function testRenderAmendments()
		{
			$db = new FakeDatabase();

			$statement = new FakeStatement();
			$statement->fetchAllFakeResult = FakeDatabase::createResultRowsForColumns(
				array('ph_company', 'ph_pub', 'ph_part', 'ph_title', 'ph_pubdate'),
				array(array(1, 4496, 'DEC-15-YWZA-DN1', 'DDT (Dynamic Debugging Technique) Utility Program', '1970-04'),
					array(1, 3301, 'DEC-15-YWZA-DN3', 'SGEN System Generator Utility Program', '1970-09')));
			$amendmentQuery = "SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`,`ph_pubdate` "
				. "FROM `PUB` JOIN `PUBHISTORY` ON `pub_id` = `ph_pub` WHERE `ph_amend_pub`=3 ORDER BY `ph_amend_serial`";
			$db->queryFakeResultsForQuery[$amendmentQuery] = $statement;

			$statement = new FakeStatement();
			$statement->fetchAllFakeResult = FakeDatabase::createResultRowsForColumns(
				array('tag_text'),
				array(array('RSX-11M Version 4.0'),
					array('RSX-11M-PLUS Version 2.0')));
			$tagQuery = 'SELECT `tag_text` FROM `TAG`,`PUBTAG` WHERE `TAG`.`id`=`PUBTAG`.`tag` AND `TAG`.`class`="os" AND `pub`=3';
			$db->queryFakeResultsForQuery[$tagQuery] = $statement;

			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderAmendments(3);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<tr valign="top"><td>Amended&nbsp;by:</td>'
				. '<td><ul class="citelist"><li>DEC-15-YWZA-DN1, <a href="../details.php/1,4496"><cite>DDT (Dynamic Debugging Technique) Utility Program</cite></a> (1970-04) <b>OS:</b> RSX-11M Version 4.0, RSX-11M-PLUS Version 2.0</li>'
				. '<li>DEC-15-YWZA-DN3, <a href="../details.php/1,3301"><cite>SGEN System Generator Utility Program</cite></a> (1970-09) <b>OS:</b> RSX-11M Version 4.0, RSX-11M-PLUS Version 2.0</li>'
				. "</ul></td></tr>\n", $output);
		}
		
		public function testRenderOSTagsEmpty()
		{
			$db = new FakeDatabase();
			$statement = new FakeStatement();
			$statement->fetchAllFakeResult = FakeDatabase::createResultRowsForColumns(
				array('tag_text'),
				array());
			$db->queryFakeResults = $statement;
			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderOSTags(3);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('', $output);
		}
		
		public function testRenderOSTagsTwoTags()
		{
			$db = new FakeDatabase();
			$statement = new FakeStatement();
			$statement->fetchAllFakeResult = FakeDatabase::createResultRowsForColumns(
				array('tag_text'),
				array(array('RSX-11M Version 4.0'),
					array('RSX-11M-PLUS Version 2.0')));
			$tagQuery = "SELECT `tag_text` FROM `TAG`,`PUBTAG` WHERE `TAG`.`id`=`PUBTAG`.`tag` AND `TAG`.`class`='os' AND `pub`=3";
			$db->queryFakeResultsForQuery[$tagQuery] = $statement;
			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderOSTags(3);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals("<tr><td>Operating System:</td><td>RSX-11M Version 4.0, RSX-11M-PLUS Version 2.0</td></tr>\n", $output);
		}
		
		public function testRenderLongDescriptionDoesNothing()
		{
			$db = new FakeDatabase();
			$manx = Manx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderLongDescription(3);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('', $output);
		}
		
		public function XtestRenderDetail()
		{
			$db = new FakeDatabase();
			$statement = new FakeStatement();
			$statement->fetchAllFakeResult = FakeDatabase::createResultRowsForColumns(
				array('pub_id', 'name', 'ph_part', 'ph_pubdate', 'ph_title', 'ph_abstract', 'ph_revision', 'ph_ocr_file', 'ph_cover_image', 'ph_lang', 'ph_keywords'),
				array(array(3, 'Digital Equipment Corporation', 'AA-K336A-TK', NULL, 'GIGI/ReGIS Handbook', NULL, '', NULL, NULL, '+en', 'VK100')));
			$detailQuery = 'SELECT `pub_id`, `COMPANY`.`name`, '
				. 'IFNULL(`ph_part`, "") AS `ph_part`, `ph_pubdate`, '
				. '`ph_title`, `ph_abstract`, '
				. 'IFNULL(`ph_revision`, "") AS `ph_revision`, `ph_ocr_file`, '
				. '`ph_cover_image`, `ph_lang`, `ph_keywords` '
				. 'FROM `PUB` '
				. 'JOIN `PUBHISTORY` ON `pub_history`=`ph_id` '
				. 'JOIN `COMPANY` ON `ph_company`=`COMPANY`.`id` '
				. 'WHERE 1=1 AND `pub_id`=3';
			$db->queryFakeResultsForQuery[$detailQuery] = $statement;
			
			$manx = new ManxRenderDetailTester($db);
			ob_start();
			$manx->renderDetails('/1,3');
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertTrue($db->queryCalled);
			$this->assertEquals($amendmentQuery, $db->queryLastStatement);
			$this->assertTrue($manx->renderLanguageCalled);
			$this->assertEquals('+en', $manx->renderLanguageLastLanguage);
			$this->assertTrue($manx->renderAmendmentsCalled);
			$this->assertEquals(3, $manx->renderAmendmentsLastPubId);
			$this->assertTrue($manx->renderOSTagsCalled);
			$this->assertEquals(3, $manx->renderOSTagsLastPubId);
			$this->assertTrue($manx->renderLongDescriptionCalled);
			$this->assertEquals(3, $manx->renderLongDescriptionLastPubId);
			$this->assertTrue($manx->renderCitationsCalled);
			$this->assertEquals(3, $manx->renderCitationsLastPubId);
			$this->assertTrue($manx->renderSupersessionsCalled);
			$this->assertEquals(3, $manx->renderSupersessionsLastPubId);
			$this->assertTrue($manx->renderTableOfContentsCalled);
			$this->assertEquals(3, $manx->renderTableOfContentsLastPubId);
			$this->assertTrue($manx->renderCopiesCalled);
			$this->assertEquals(3, $manx->renderCopiesLastPubId);
			$this->assertEquals('<div class="det"><h1>GIGI/ReGIS Handbook</h1>
<table><tbody><tr><td>Company:</td><td>Digital Equipment Corporation</td></tr>
<tr><td>Part:</td><td>AA-K336A-TK</td></tr>
<tr><td>Date:</td><td></td></tr>
<tr><td>Keywords:</td><td>VK100</td></tr>
</tbody>
</table>
', $output);
		}
	}
?>
