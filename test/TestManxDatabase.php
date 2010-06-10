<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'ManxDatabase.php';
	require_once 'test/FakeDatabase.php';
	require_once 'test/FakeStatement.php';

	class TestManxDatabase extends PHPUnit_Framework_TestCase
	{
		private $_db;
		private $_manxDb;
		private $_statement;
		
		public function testConstruct()
		{
			$this->createInstance();
			$this->assertTrue(!is_null($this->_manxDb) && is_object($this->_manxDb));
		}
		
		public function testGetDocumentCount()
		{
			$query = "SELECT COUNT(*) FROM `PUB`";
			$this->configureCountForQuery(2, $query);
			$count = $this->_manxDb->getDocumentCount();
			$this->assertCountForQuery(2, $count, $query);
		}
		
		public function testGetOnlineDocumentCount()
		{
			$query = "SELECT COUNT(DISTINCT `pub`) FROM `COPY`";
			$this->configureCountForQuery(12, $query);
			$count = $this->_manxDb->getOnlineDocumentCount();
			$this->assertCountForQuery(12, $count, $query);
		}
		
		public function testGetSiteCount()
		{
			$query = "SELECT COUNT(*) FROM `SITE`";
			$this->configureCountForQuery(43, $query);
			$count = $this->_manxDb->getSiteCount();
			$this->assertCountForQuery(43, $count, $query);
		}

		public function testGetSiteList()
		{
			$this->createInstance();
			$query = "SELECT `url`,`description`,`low` FROM `SITE` WHERE `live`='Y' ORDER BY `siteid`";
			$this->configureStatementFetchAllResults($query,
				FakeDatabase::createResultRowsForColumns(
					array('url', 'description', 'low'),
					array(array('http://www.dec.com', 'DEC', false), array('http://www.hp.com', 'HP', true))));
			$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
			$sites = $this->_manxDb->getSiteList();
			$this->assertQueryCalledForSql($query);
			$this->assertEquals(2, count($sites));
			$this->assertEquals('http://www.dec.com', $sites[0]['url']);
			$this->assertEquals('http://www.hp.com', $sites[1]['url']);
		}
		
		public function testGetCompanyList()
		{
			$this->createInstance();
			$query = "SELECT `id`,`name` FROM `COMPANY` WHERE `display` = 'Y' ORDER BY `sort_name`";
			$this->configureStatementFetchAllResults($query,
				array(
					array('id' => 1, 'name' => "DEC"),
					array('id' => 2, 'name' => "HP")));
			$companies = $this->_manxDb->getCompanyList();
			$this->assertQueryCalledForSql($query);
			$this->assertEquals(2, count($companies));
			$this->assertEquals(1, $companies[0]['id']);
			$this->assertEquals('DEC', $companies[0]['name']);
			$this->assertEquals(2, $companies[1]['id']);
			$this->assertEquals('HP', $companies[1]['name']);
		}
		
		private function createInstance()
		{
			$this->_db = new FakeDatabase();
			$this->_manxDb = ManxDatabase::getInstanceForDatabase($this->_db);
			$this->_statement = new FakeStatement();
		}

		private function configureCountForQuery($expectedCount, $query)
		{
			$this->createInstance();
			$this->_statement->fetchFakeResult = array($expectedCount);
			$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
		}
		
		private function assertCountForQuery($expectedCount, $count, $query)
		{
			$this->assertQueryCalledForSql($query);
			$this->assertTrue($this->_statement->fetchCalled);
			$this->assertEquals($expectedCount, $count);
		}
		
		private function assertQueryCalledForSql($sql)
		{
			$this->assertTrue($this->_db->queryCalled);
			$this->assertEquals($sql, $this->_db->queryLastStatement);
		}

		private function configureStatementFetchAllResults($query, $results)
		{
			$this->_statement->fetchAllFakeResult = $results;
			$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
		}
	}
?>
