<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'Searcher.php';
	require_once 'test/FakeDatabase.php';
	require_once 'test/FakeStatement.php';
	require_once 'test/FakeFormatter.php';

	class TestSearcher extends PHPUnit_Framework_TestCase
	{
		public function testRenderCompanies()
		{
			$db = new FakeDatabase();
			$db->queryFakeResults = array(
				array('id' => 1, 'name' => 'DEC'),
				array('id' => 2, 'name' => '3Com'));
			$searcher = Searcher::getInstance($db);
			ob_start();
			$searcher->renderCompanies(1);
			$this->assertTrue($db->queryCalled);
			$this->assertEquals("SELECT `id`,`name` FROM `COMPANY` ORDER BY `sort_name`", $db->queryLastStatement);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertEquals('<select id="CP" name="cp"><option value="1" selected>DEC</option><option value="2">3Com</option></select>', $output);
		}
		
		public function testParameterSourceHttpGet()
		{
			$get = array('cp' => 1);
			$post = array();
			$source = Searcher::parameterSource($get, $post);
			$this->assertEquals($get, $source);
		}
		
		public function testParameterSourceHttpPost()
		{
			$get = array();
			$post = array('cp' => 1);
			$source = Searcher::parameterSource($get, $post);
			$this->assertEquals($post, $source);
		}
		
		public function testParameterSourceDefaultGet()
		{
			$get = array('q' => 'terminal');
			$post = array();
			$source = Searcher::parameterSource($get, $post);
			$this->assertEquals($get, $source);
		}
		
		public function testMatchClauseForKeyword()
		{
			$keyword = "terminal";
			$db = new FakeDatabase();
			$searcher = Searcher::getInstance($db);
			$clause = $searcher->matchClauseForKeywords($keyword);
			$this->assertEquals(" AND ((`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
				. "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
		}
		
		public function testMatchClauseForMultipleKeywords()
		{
			$keyword = "graphics terminal";
			$db = new FakeDatabase();
			$searcher = Searcher::getInstance($db);
			$clause = $searcher->matchClauseForKeywords($keyword);
			$this->assertEquals(" AND ((`ph_title` LIKE '%graphics%' OR `ph_keywords` LIKE '%graphics%' "
				. "OR `ph_match_part` LIKE '%GRAPHICS%' OR `ph_match_alt_part` LIKE '%GRAPHICS%') "
				. "AND (`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
				. "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
		}
		
		public function testNormalizePartNumberNotString()
		{
			$this->assertEquals('', Searcher::normalizePartNumber(array()));
		}
		
		public function testNormalizePartNumberLowerCase()
		{
			$this->assertEquals('UC', Searcher::normalizePartNumber('uc'));
		}
		
		public function testNormalizePartNumberNonAlphaNumeric()
		{
			$this->assertEquals('UC122', Searcher::normalizePartNumber(' !u,c,1,2,2 ,./<>?;' . "'" . ':"[]{}\\|`~!@#$%^&*()'));
		}
		
		public function testNormalizePartNumberLetterOhIsZero()
		{
			$this->assertEquals('UC1220', Searcher::normalizePartNumber(' !u,c,1,2,2,o ,./<>?;' . "'" . ':"[]{}\\|`~!@#$%^&*()'));
		}

		public function testCleanSqlWordNotString()
		{
			$this->assertEquals('', Searcher::cleanSqlWord(array()));
		}
				
		public function testCleanSqlWordNoSpecials()
		{
			$this->assertEquals('cleanWord', Searcher::cleanSqlWord('cleanWord'));
		}
		
		public function testCleanSqlWordPercent()
		{
			$this->assertEquals('percent\\%Word', Searcher::cleanSqlWord('percent%Word'));
		}
		
		public function testCleanSqlWordQuote()
		{
			$this->assertEquals("quote\\'Word", Searcher::cleanSqlWord("quote'Word"));
		}
		
		public function testCleanSqlWordUnderline()
		{
			$this->assertEquals('underline\\_Word', Searcher::cleanSqlWord('underline_Word'));
		}
		
		public function testCleanSqlWordBackslash()
		{
			$this->assertEquals('backslash\\\\Word', Searcher::cleanSqlWord('backslash\\Word'));
		}
		
		public function testSearchResultsSingleRow()
		{
			$db = new FakeDatabase();
			$stmt = new FakeStatement();
			$rows = array(
				array('pub_id' => 1,
					'ph_part' => '',
					'ph_title' => '',
					'pub_has_online_copies' => '',
					'ph_abstract' => '',
					'pub_has_toc' => '',
					'pub_superseded' => '',
					'ph_pubdate' => '',
					'ph_revision' => '',
					'ph_company' => '',
					'ph_alt_part' => '',
					'ph_pubtype' => '')
				);
			$stmt->fetchAllFakeResult = $rows;
			$formatter = new FakeFormatter();
			$searcher = Searcher::getInstance($db);
			$keywords = "graphics terminal";
			$matchClause = $searcher->matchClauseForKeywords($keywords);
			$company = 1;
			$mainQuery = "SELECT `pub_id`, `ph_part`, `ph_title`,"
				. " `pub_has_online_copies`, `ph_abstract`, `pub_has_toc`,"
				. " `pub_superseded`, `ph_pubdate`, `ph_revision`,"
				. " `ph_company`, `ph_alt_part`, `ph_pubtype` FROM `PUB`"
				. " JOIN `PUBHISTORY` ON `pub_history` = `ph_id`"
				. " WHERE `pub_has_online_copies` $matchClause"
				. " AND `ph_company`=$company"
				. " ORDER BY `ph_sort_part`, `ph_pubdate`, `pub_id`";
			$db->queryFakeResultsForQuery[$mainQuery] = $stmt;
			$tagStmt = new FakeStatement();
			$tagStmt->fetchAllFakeResult = array(array('tag_text' => 'OpenVMS VAX Version 6.0'));
			$tagQuery = "SELECT `tag_text` FROM `TAG`,`PUBTAG` WHERE `TAG`.`id`=`PUBTAG`.`tag` and `TAG`.`class` = 'os' AND `PUB`=1";
			$db->queryFakeResultsForQuery[$tagQuery] = $tagStmt;
			$searcher->renderSearchResults($formatter, $company, $keywords, true);
			$this->assertTrue($db->queryCalledForStatement[$mainQuery]);
			$this->assertTrue($stmt->fetchAllCalled);
			$this->assertTrue($formatter->renderResultsBarCalled);
			$this->assertTrue($db->queryCalledForStatement[$tagQuery]);
			$this->assertTrue($tagStmt->fetchAllCalled);
			$this->assertEquals(array(), $formatter->renderResultsBarLastIgnoredWords);
			$this->assertEquals(array('graphics', 'terminal'), $formatter->renderResultsBarLastSearchWords);
			$this->assertEquals(1, $formatter->renderResultsBarLastStart);
			$this->assertEquals(1, $formatter->renderResultsBarLastEnd);
			$this->assertEquals(1, $formatter->renderResultsBarLastTotal);
			$this->assertTrue($formatter->renderPageSelectionBarCalled);
			$this->assertEquals(1, $formatter->renderPageSelectionBarLastStart);
			$this->assertEquals(1, $formatter->renderPageSelectionBarLastTotal);
			$this->assertEquals(10, $formatter->renderPageSelectionBarLastRowsPerPage);
			$this->assertTrue($formatter->renderResultsPageCalled);
			$rows[0]['tags'] = array('OpenVMS VAX Version 6.0');
			$this->assertEquals($rows, $formatter->renderResultsPageLastRows);
			$this->assertEquals(1, $formatter->renderResultsPageLastStart);
			$this->assertEquals(1, $formatter->renderResultsPageLastEnd);
			$this->assertEquals(2, $formatter->renderPageSelectionBarCallCount);
		}
	}
?>
