<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'Manx.php';
	require_once 'test/FakeDatabase.php';
	require_once 'test/FakeStatement.php';
	
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
	}
?>
