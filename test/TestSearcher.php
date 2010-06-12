<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'Searcher.php';
	require_once 'test/FakeDatabase.php';
	require_once 'test/FakeStatement.php';
	require_once 'test/FakeFormatter.php';
	require_once 'test/FakeManxDatabase.php';

	class TestSearcher extends PHPUnit_Framework_TestCase
	{
		public function testRenderCompanies()
		{
			$db = new FakeManxDatabase();
			$db->getCompanyListFakeResult = array(
				array('id' => 1, 'name' => 'DEC'),
				array('id' => 2, 'name' => '3Com'),
				array('id' => 3, 'name' => 'AT&T'));
			$searcher = Searcher::getInstance(new FakeDatabase(), $db);
			ob_start();
			$searcher->renderCompanies(1);
			$output = ob_get_contents();
			ob_end_clean();
			$this->assertTrue($db->getCompanyListCalled);
			$this->assertEquals('<select id="CP" name="cp">'
				. '<option value="1" selected>DEC</option>'
				. '<option value="2">3Com</option>'
				. '<option value="3">AT&amp;T</option>'
				. '</select>', $output);
		}

		public function testParameterSourceHttpGet()
		{
			$get = array('cp' => 1);
			$post = array();
			$source = Searcher::parameterSource($get, $post);
			$this->assertEquals($get, $source);
			$this->assertTrue(array_key_exists('cp', $source));
		}

		public function testParameterSourceHttpPost()
		{
			$get = array();
			$post = array('cp' => 1);
			$source = Searcher::parameterSource($get, $post);
			$this->assertEquals($post, $source);
			$this->assertTrue(array_key_exists('cp', $source));
		}

		public function testParameterSourceDefaultGet()
		{
			$get = array('q' => 'terminal');
			$post = array();
			$source = Searcher::parameterSource($get, $post);
			$this->assertEquals($get, $source);
			$this->assertTrue(array_key_exists('q', $source));
		}

		public function testMatchClauseForKeyword()
		{
			$keyword = "terminal";
			$db = new FakeDatabase();
			$searcher = Searcher::getInstance($db, new FakeManxDatabase());
			$clause = $searcher->matchClauseForKeywords($keyword);
			$this->assertEquals(" AND ((`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
				. "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
		}

		public function testMatchClauseForMultipleKeywords()
		{
			$keyword = "graphics terminal";
			$db = new FakeDatabase();
			$searcher = Searcher::getInstance($db, new FakeManxDatabase());
			$clause = $searcher->matchClauseForKeywords($keyword);
			$this->assertEquals(" AND ((`ph_title` LIKE '%graphics%' OR `ph_keywords` LIKE '%graphics%' "
				. "OR `ph_match_part` LIKE '%GRAPHICS%' OR `ph_match_alt_part` LIKE '%GRAPHICS%') "
				. "AND (`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
				. "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
		}

		public function testFilterSearchKeywordsIgnored()
		{
			$this->assertEquals(array(), Searcher::filterSearchKeywords("a an it on in at", $ignoredWords));
			$this->assertEquals(array('a', 'an', 'it', 'on', 'in', 'at'), $ignoredWords);
		}
		
		public function testFilterSearchKeywordsAcceptable()
		{
			$ignoredWords = array();
			$this->assertEquals(array('one', 'two'), Searcher::filterSearchKeywords("one two", $ignoredWords));
			$this->assertEquals(array(), $ignoredWords);
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
			$manxDb = new FakeManxDatabase();
			$searcher = Searcher::getInstance($db, $manxDb);
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
			
			$manxDb->getOSTagsForPubFakeResult = array('OpenVMS VAX Version 6.0');

			$searcher->renderSearchResults($formatter, $company, $keywords, true);
			$this->assertTrue($db->queryCalledForStatement[$mainQuery]);
			$this->assertTrue($stmt->fetchAllCalled);
			$this->assertTrue($formatter->renderResultsBarCalled);
			$this->assertTrue($manxDb->getOSTagsForPubCalled);
			$this->assertEquals(1, $manxDb->getOSTagsForPubLastPubId);
			$this->assertEquals(array(), $formatter->renderResultsBarLastIgnoredWords);
			$this->assertEquals(array('graphics', 'terminal'), $formatter->renderResultsBarLastSearchWords);
			$this->assertEquals(0, $formatter->renderResultsBarLastStart);
			$this->assertEquals(0, $formatter->renderResultsBarLastEnd);
			$this->assertEquals(1, $formatter->renderResultsBarLastTotal);
			$this->assertTrue($formatter->renderPageSelectionBarCalled);
			$this->assertEquals(0, $formatter->renderPageSelectionBarLastStart);
			$this->assertEquals(1, $formatter->renderPageSelectionBarLastTotal);
			$this->assertEquals(10, $formatter->renderPageSelectionBarLastRowsPerPage);
			$this->assertTrue($formatter->renderResultsPageCalled);
			$rows[0]['tags'] = array('OpenVMS VAX Version 6.0');
			$this->assertEquals($rows, $formatter->renderResultsPageLastRows);
			$this->assertEquals(0, $formatter->renderResultsPageLastStart);
			$this->assertEquals(0, $formatter->renderResultsPageLastEnd);
			$this->assertEquals(2, $formatter->renderPageSelectionBarCallCount);
		}
	}
?>
