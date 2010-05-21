<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'ProductionManx.php';
	require_once 'test/FakeDatabase.php';
	
	class FakeStatement
	{
		public $fetchCalled;
		public $fetchFakeResult;
		
		public function __construct($fetchResult)
		{
			$this->fetchCalled = false;
			$this->fetchFakeResult = $fetchResult;
		}
		
		public function fetch()
		{
			$this->fetchCalled = true;
			return $this->fetchFakeResult;			
		}
	}
	
	class TestManx extends PHPUnit_Framework_TestCase
	{
		public function testRenderDefaultCompanies()
		{
			$db = new FakeDatabase();
			$db->queryFakeResults = array(
				array('id' => 1, 'name' => 'DEC'),
				array('id' => 2, 'name' => '3Com'));
			$manx = ProductionManx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderDefaultCompanies();
			$this->assertTrue($db->queryCalled);
			$this->assertEquals("SELECT `id`,`name` FROM `COMPANY` ORDER BY `sort_name`", $db->queryLastStatement);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<select id="CP" name="cp"><option value="1 selected>DEC</option><option value="2>3Com</option></select>', $output);
		}
		
		public function testRenderDocumentSummary()
		{
			$db = new FakeDatabase();
			$db->queryFakeResultsForQuery = array(
				"SELECT COUNT(*) FROM `PUB`" => new FakeStatement(array(12)),
				"SELECT COUNT(DISTINCT `pub`) FROM `COPY`" => new FakeStatement(array(24)),
				"SELECT COUNT(*) FROM `SITE`" => new FakeStatement(array(43))
				);
			$manx = ProductionManx::getInstanceForDatabase($db);
			ob_start();
			$manx->renderDocumentSummary();
			$this->assertTrue($db->queryCalled);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals("12 manuals, 24 of which are online, at 43 websites", $output);
		}
	}
?>
