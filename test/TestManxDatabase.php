<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'ManxDatabase.php';
	require_once 'test/FakeDatabase.php';
	require_once 'test/FakeStatement.php';
	
	class TestManxDatabase extends PHPUnit_Framework_TestCase
	{
		public function testConstruct()
		{
			$db = new FakeDatabase();
			$manxDb = ManxDatabase::getInstanceForDatabase($db);
			$this->assertTrue(!is_null($manxDb) && is_object($manxDb));
		}
		
		public function testGetSiteList()
		{
			$db = new FakeDatabase();
			$query = "SELECT `url`,`description`,`low` FROM `SITE` WHERE `live`='Y' ORDER BY `siteid`";
			$statement = new FakeStatement();
			$statement->fetchAllFakeResult = FakeDatabase::createResultRowsForColumns(
				array('url', 'description', 'low'),
				array(array('http://www.dec.com', 'DEC', false), array('http://www.hp.com', 'HP', true)));
			$db->queryFakeResultsForQuery[$query] = $statement;
			$manxDb = ManxDatabase::getInstanceForDatabase($db);
			$sites = $manxDb->getSiteList();
			$this->assertTrue($db->queryCalled);
			$this->assertEquals($query, $db->queryLastStatement);
			$this->assertEquals(2, count($sites));
			$this->assertEquals('http://www.dec.com', $sites[0]['url']);
			$this->assertEquals('http://www.hp.com', $sites[1]['url']);
		}
	}
?>
