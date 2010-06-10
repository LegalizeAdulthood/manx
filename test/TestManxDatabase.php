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
		
		public function testGetDocumentCount()
		{
			$db = new FakeDatabase();
			$statement = new FakeStatement();
			$statement->fetchFakeResult = array(2);
			$query = "SELECT COUNT(*) FROM `PUB`";
			$db->queryFakeResultsForQuery[$query] = $statement;
			$manxDb = ManxDatabase::getInstanceForDatabase($db);
			$count = $manxDb->getDocumentCount();
			$this->assertTrue($db->queryCalled);
			$this->assertEquals($query, $db->queryLastStatement);
			$this->assertTrue($statement->fetchCalled);
			$this->assertEquals(2, $count);
		}
		
		public function testGetOnlineDocumentCount()
		{
			$db = new FakeDatabase();
			$statement = new FakeStatement();
			$statement->fetchFakeResult = array(12);
			$query = "SELECT COUNT(DISTINCT `pub`) FROM `COPY`";
			$db->queryFakeResultsForQuery[$query] = $statement;
			$manxDb = ManxDatabase::getInstanceForDatabase($db);
			$count = $manxDb->getOnlineDocumentCount();
			$this->assertTrue($db->queryCalled);
			$this->assertEquals($query, $db->queryLastStatement);
			$this->assertEquals(12, $count);
		}
		
		public function testGetSiteCount()
		{
			$db = new FakeDatabase();
			$statement = new FakeStatement();
			$statement->fetchFakeResult = array(43);
			$query = "SELECT COUNT(*) FROM `SITE`";
			$db->queryFakeResultsForQuery[$query] = $statement;
			$manxDb = ManxDatabase::getInstanceForDatabase($db);
			$count = $manxDb->getSiteCount();
			$this->assertTrue($db->queryCalled);
			$this->assertEquals($query, $db->queryLastStatement);
			$this->assertEquals(43, $count);
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

		private function fakeStatementFetchResults($results)
		{
			$stmt = new FakeStatement();
			$stmt->fetchFakeResult = $results;
			return $stmt;
		}

		public function testGetCompanyList()
		{
			$db = new FakeDatabase();
			$query = "SELECT `id`,`name` FROM `COMPANY` WHERE `display` = 'Y' ORDER BY `sort_name`";
			$statement = new FakeStatement();
			$statement->fetchAllFakeResult = array(
				array('id' => 1, 'name' => "DEC"),
				array('id' => 2, 'name' => "HP"));
			$db->queryFakeResultsForQuery[$query] = $statement;
			$manxDb = ManxDatabase::getInstanceForDatabase($db);
			$companies = $manxDb->getCompanyList();
			$this->assertTrue($db->queryCalled);
			$this->assertEquals(2, count($companies));
			$this->assertEquals(1, $companies[0]['id']);
			$this->assertEquals('DEC', $companies[0]['name']);
			$this->assertEquals(2, $companies[1]['id']);
			$this->assertEquals('HP', $companies[1]['name']);
		}
	}
?>
