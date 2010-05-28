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
			$stmt->fetchAllFakeResult = array(
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
			$db->queryFakeResults = $stmt;
			$formatter = new FakeFormatter();
			$searcher = Searcher::getInstance($db);
			$keywords = "graphics terminal";
			$matchClause = $searcher->matchClauseForKeywords($keywords);
			$company = 1;
			$searcher->renderSearchResults($formatter, $company, $keywords, true);
			$this->assertTrue($db->queryCalled);
			$this->assertEquals("SELECT `pub_id`, `ph_part`, `ph_title`,"
				. " `pub_has_online_copies`, `ph_abstract`, `pub_has_toc`,"
				. " `pub_superseded`, `ph_pubdate`, `ph_revision`,"
				. " `ph_company`, `ph_alt_part`, `ph_pubtype` FROM `PUB`"
				. " JOIN `PUBHISTORY` ON `pub_history` = `ph_id`"
				. " WHERE `pub_has_online_copies` $matchClause"
				. " AND `ph_company`=$company"
				. " ORDER BY `ph_sort_part`, `ph_pubdate`, `pub_id`",
				$db->queryLastStatement);
			$this->assertTrue($stmt->fetchAllCalled);
			$this->assertTrue($formatter->renderResultsBarCalled);
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
			$this->assertEquals($stmt->fetchAllFakeResult, $formatter->renderResultsPageLastRows);
			$this->assertEquals(1, $formatter->renderResultsPageLastStart);
			$this->assertEquals(1, $formatter->renderResultsPageLastEnd);
			$this->assertEquals(2, $formatter->renderPageSelectionBarCallCount);
/*
			$this->assertEquals('<div class="resbar">Searching for "graphics" and "terminal". Results <b>1 - 10</b> of <b>16</b>.</div>
<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;<a class="navpage" href="search.php?q=graphics%20terminal;start=10;on=on;cp=1">2</a>&nbsp;&nbsp;<a href="search.php?q=graphics%20terminal;start=10;on=on;cp=1"><b>Next</b></a></div>
<table class="restable"><thead><tr><th>Part</th><th>Date</th><th>Title</th><th class="last">Status</th></tr></thead><tbody><tr valign="top">
<td>AA-4949A-TC</td>
<td>1977-02</td>
<td><a href="details.php/1,1">VT55 Programming Manual</a></td>
<td>Online, ToC</td>
</tr>
<tr valign="top">
<td>EK-VT100-TM-003</td>
<td>1982-07</td>
<td><a href="details.php/1,4071">VT100 Series Video Terminal Technical Manual</a></td>
<td>Online, ToC</td>
</tr>
<tr valign="top">
<td>EK-VT125-GI-001</td>
<td>1982-05</td>
<td><a href="details.php/1,6262">VT125 ReGIS Primer</a></td>
<td>Online, ToC</td>
</tr>
<tr valign="top">
<td>EK-VT125-UG-001</td>
<td>1981-09</td>
<td><a class="ss" href="details.php/1,3086">VT125 Graphics Terminal User Guide</a></td>
<td>Online, Superseded</td>
</tr>
<tr valign="top">
<td>EK-VT125-UG-002</td>
<td>1982-06</td>
<td><a href="details.php/1,2945">VT125 Graphics Terminal User Guide</a></td>
<td>Online, ToC</td>
</tr>
<tr valign="top">
<td>EK-VT240-HR-002</td>
<td>1984-09</td>
<td><a href="details.php/1,2966">VT240 Series Programmer Pocket Guide</a></td>
<td>Online, ToC</td>
</tr>
<tr valign="top">
<td>EK-VT240-PS-002</td>
<td>1984-10</td>
<td><a href="details.php/1,2969">VT240 Series Pocket Service Guide</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td>EK-VT240-RM-002</td>
<td>1984-10</td>
<td><a href="details.php/1,2970">VT240 Series Programmer Reference Manual</a></td>
<td>Online</td>
</tr>
<tr valign="top">
<td>EK-VT330-PS-002</td>
<td>1988-04</td>
<td><a href="details.php/1,2986">VT330 Pocket Service Guide</a></td>
<td>Online, ToC</td>
</tr>
<tr valign="top">
<td>EK-VT340-IP-003</td>
<td>1990-05-31</td>
<td><a href="details.php/1,2988">VT340 Video Terminal Illustrated Parts Breakdown</a></td>
<td>Online</td>
</tr>
</tbody></table><div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;<a class="navpage" href="search.php?q=graphics%20terminal;start=10;on=on;cp=1">2</a>&nbsp;&nbsp;<a href="search.php?q=graphics%20terminal;start=10;on=on;cp=1"><b>Next</b></A></div>',
			$output);
*/
		}
	}
?>
