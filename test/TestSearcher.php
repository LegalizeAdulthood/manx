<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'Searcher.php';
	require_once 'test/FakeDatabase.php';
	require_once 'test/FakeStatement.php';

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
				. "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` like '%TERMINAL%'))", $clause);
		}
		
		public function testMatchClauseForMultipleKeywords()
		{
			$keyword = "graphics terminal";
			$db = new FakeDatabase();
			$searcher = Searcher::getInstance($db);
			$clause = $searcher->matchClauseForKeywords($keyword);
			$this->assertEquals(" AND ((`ph_title` LIKE '%graphics%' OR `ph_keywords` LIKE '%graphics%' "
				. "OR `ph_match_part` LIKE '%GRAPHICS%' OR `ph_match_alt_part` like '%GRAPHICS%') "
				. "AND (`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
				. "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` like '%TERMINAL%'))", $clause);
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
	}
?>
