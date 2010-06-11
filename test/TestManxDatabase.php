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
			$this->assertColumnValuesForRows($sites, 'url', array('http://www.dec.com', 'http://www.hp.com'));
		}
		
		public function testGetCompanyList()
		{
			$this->createInstance();
			$query = "SELECT `id`,`name` FROM `COMPANY` WHERE `display` = 'Y' ORDER BY `sort_name`";
			$expected = array(
					array('id' => 1, 'name' => "DEC"),
					array('id' => 2, 'name' => "HP"));
			$this->configureStatementFetchAllResults($query, $expected);
			$companies = $this->_manxDb->getCompanyList();
			$this->assertQueryCalledForSql($query);
			$this->assertEquals($expected, $companies);
		}
		
		public function testGetDisplayLanguage()
		{
			$this->createInstance();
			$query = "SELECT IF(LOCATE(';',`eng_lang_name`),LEFT(`eng_lang_name`,LOCATE(';',`eng_lang_name`)-1),`eng_lang_name`) FROM `LANGUAGE` WHERE `lang_alpha_2`='fr'";
			$this->configureStatementFetchResult($query, 'French');
			$display = $this->_manxDb->getDisplayLanguage('fr');
			$this->assertQueryCalledForSql($query);
			$this->assertEquals('French', $display);
		}
		
		public function testGetOSTagsForPub()
		{
			$this->createInstance();
			$query = "SELECT `tag_text` FROM `TAG`,`PUBTAG` WHERE `TAG`.`id`=`PUBTAG`.`tag` AND `TAG`.`class`='os' AND `pub`=5";
			$this->configureStatementFetchAllResults($query,
				FakeDatabase::createResultRowsForColumns(array('tag_text'),
					array(array('RSX-11M Version 4.0'), array('RSX-11M-PLUS Version 2.0'))));
			$tags = $this->_manxDb->getOSTagsForPub(5);
			$this->assertQueryCalledForSql($query);
			$this->assertEquals($tags, array('RSX-11M Version 4.0', 'RSX-11M-PLUS Version 2.0'));
		}
		
		public function testGetAmendmentsForPub()
		{
			$this->createInstance();
			$query = "SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`,`ph_pubdate` "
				. "FROM `PUB` JOIN `PUBHISTORY` ON `pub_id` = `ph_pub` WHERE `ph_amend_pub`=3 ORDER BY `ph_amend_serial`";
			$pubId = 3;
			$this->configureStatementFetchAllResults($query,
				FakeDatabase::createResultRowsForColumns(
					array('ph_company', 'ph_pub', 'ph_part', 'ph_title', 'ph_pubdate'),
					array(array(1, 4496, 'DEC-15-YWZA-DN1', 'DDT (Dynamic Debugging Technique) Utility Program', '1970-04'),
						array(1, 3301, 'DEC-15-YWZA-DN3', 'SGEN System Generator Utility Program', '1970-09'))));
			$amendments = $this->_manxDb->getAmendmentsForPub($pubId);
			$this->assertQueryCalledForSql($query);
			$this->assertArrayHasLength($amendments, 2);
			$this->assertColumnValuesForRows($amendments, 'ph_pub', array(4496, 3301));
		}
		
		public function testGetLongDescriptionForPubDoesNothing()
		{
			$this->createInstance();
			$pubId = 3;
			$query = "SELECT 'html_text' FROM `LONG_DESC` WHERE `pub`=3 ORDER BY `line`";
			$this->configureStatementFetchAllResults($query,
				FakeDatabase::createResultRowsForColumns(array('html_text'),
					array(array('<p>This is paragraph one.</p>'), array('<p>This is paragraph two.</p>'))));
			$longDescription = $this->_manxDb->getLongDescriptionForPub($pubId);
			$this->assertFalse($this->_db->queryCalled);
			/*
			TODO: LONG_DESC table missing
			$this->assertQueryCalledForSql($query);
			$this->assertEquals(array('<p>This is paragraph one.</p>', '<p>This is paragraph two.</p>'), $longDescription);
			*/
		}
		
		public function testGetCitationsForPub()
		{
			$this->createInstance();
			$pubId = 72;
			$query = 'SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` '
				. 'FROM `CITEPUB` `C`'
				. ' JOIN `PUB` ON (`C`.`pub`=`pub_id` AND `C`.`mentions_pub`=72)'
				. ' JOIN `PUBHISTORY` ON `pub_history`=`ph_id`';
			$this->configureStatementFetchAllResults($query,
				FakeDatabase::createResultRowsForColumns(
					array('ph_company', 'ph_pub', 'ph_part', 'ph_title'),
					array(array(1, 123, 'EK-306AA-MG-001', 'KA655 CPU System Maintenance'))));
			$citations = $this->_manxDb->getCitationsForPub($pubId);
			$this->assertQueryCalledForSql($query);
			$this->assertArrayHasLength($citations, 1);
			$this->assertEquals('EK-306AA-MG-001', $citations[0]['ph_part']);
		}
		
		public function testGetTableOfContentsForPubFullContents()
		{
			$this->createInstance();
			$pubId = 123;
			$query = "SELECT `level`,`label`,`name` FROM `TOC` WHERE `pub`=123 ORDER BY `line`";
			$this->configureStatementFetchAllResults($query,
				FakeDatabase::createResultRowsForColumns(
					array('level', 'label', 'name'),
					array(
						array(1, 'Chapter 2', 'Configuration'),
						array(2, '2.4', 'DSSI Configuration'),
						array(3, '2.4.4', 'DSSI Cabling'),
						array(4, '2.4.4.1', 'DSSI Bus Termination and Length'),
						array(1, 'Appendix C', 'Related Documentation'))));
			$toc = $this->_manxDb->getTableOfContentsForPub($pubId, true);
			$this->assertQueryCalledForSql($query);
			$this->assertArrayHasLength($toc, 5);
			$this->assertColumnValuesForRows($toc, 'label',
				array('Chapter 2', '2.4', '2.4.4', '2.4.4.1', 'Appendix C'));
		}

		public function testGetTableOfContentsForPubAbbreviatedContents()
		{
			$this->createInstance();
			$pubId = 123;
			$query = "SELECT `level`,`label`,`name` FROM `TOC` WHERE `pub`=123 AND `level` < 2 ORDER BY `line`";
			$this->configureStatementFetchAllResults($query,
				FakeDatabase::createResultRowsForColumns(
					array('level', 'label', 'name'),
					array(
						array(1, 'Chapter 1', 'KA655 CPU and Memory Subsystem'),
						array(1, 'Chapter 2', 'Configuration'),
						array(1, 'Chapter 3', 'KA655 Firmware'),
						array(1, 'Chapter 4', 'Troubleshooting and Diagnostics'),
						array(1, 'Appendix A', 'Configuring the KFQSA'),
						array(1, 'Appendix B', 'KA655 CPU Address Assignments'),
						array(1, 'Appendix C', 'Related Documentation'))
				));
			$toc = $this->_manxDb->getTableOfContentsForPub($pubId, false);
			$this->assertQueryCalledForSql($query);
			$this->assertArrayHasLength($toc, 7);
			$this->assertColumnValuesForRows($toc, 'label',
				array('Chapter 1', 'Chapter 2', 'Chapter 3', 'Chapter 4', 'Appendix A', 'Appendix B', 'Appendix C'));
		}
		
		public function testGetMirrorsForCopy()
		{
			$this->createInstance();
			$copyId = 7165;
			$query = "SELECT REPLACE(`url`,`original_stem`,`copy_stem`) AS `mirror_url`"
					. " FROM `COPY` JOIN `mirror` ON `COPY`.`site`=`mirror`.`site`"
					. " WHERE `copyid`=7165 ORDER BY `rank` DESC";
			$expected = array('http://bitsavers.trailing-edge.com/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
				'http://www.bighole.nl/pub/mirror/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
				'http://www.textfiles.com/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
				'http://computer-refuge.org/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
				'http://www.mirrorservice.org/sites/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf');
			$this->configureStatementFetchAllResults($query,
				FakeDatabase::createResultRowsForColumns(array('mirror_url'),
					array(array($expected[0]), array($expected[1]), array($expected[2]), array($expected[3]), array($expected[4]))));
			$mirrors = $this->_manxDb->getMirrorsForCopy($copyId);
			$this->assertQueryCalledForSql($query);
			$this->assertEquals($expected, $mirrors);
		}

		private function assertColumnValuesForRows($rows, $column, $values)
		{
			$this->assertEquals(count($rows), count($values), "different number of expected values from the number of rows");
			$i = 0;
			foreach ($rows as $row)
			{
				$this->assertTrue(array_key_exists($column, $row), sprintf("row doesn't contain key '%s'", $column));
				$this->assertEquals($row[$column], $values[$i], "expected value doesn't match value in column");
				++$i;
			}
		}
		
		private function assertArrayHasLength($value, $length)
		{
			$this->assertTrue(is_array($value));
			$this->assertEquals($length, count($value));
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
			$this->configureStatementFetchResult($query, array($expectedCount));
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

		private function configureStatementFetchResult($query, $result)
		{
			$this->_statement->fetchFakeResult = $result;
			$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
		}
		
		private function configureStatementFetchAllResults($query, $results)
		{
			$this->_statement->fetchAllFakeResult = $results;
			$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
		}
	}
?>
