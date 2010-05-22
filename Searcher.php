<?php
	require_once 'IDatabase.php';
	require_once 'ISearcher.php';
	
	class Searcher implements ISearcher
	{
		
		private $_db;

		public static function getInstance(IDatabase $db)
		{
			return new Searcher($db);
		}
		
		private function __construct($db)
		{
			$this->_db = $db;
		}
		
		public function renderCompanies($selected)
		{
			print '<select id="CP" name="cp">';
			foreach ($this->_db->query("SELECT `id`,`name` FROM `COMPANY` ORDER BY `sort_name`") as $row)
			{
				$id = $row['id'];
				print '<option value="' . $id . '"' . ($id == $selected ? ' selected' : '') . '>' . htmlspecialchars($row['name']) . '</option>';
			}
			print '</select>';
		}

		public static function parameterSource($get, $post)
		{
			if (array_key_exists('cp', $post))
			{
				return $post;
			}
			else
			{
				return $get;
			}
		}
		
		public function matchClauseForKeywords($keywords)
		{
			$searchWords = array();
			$ignoredWords = array();
			foreach (explode(' ', $keywords) as $keyword)
			{
				$keyword = trim($keyword);
				if ($keyword != '')
				{
					if (strlen($keyword) > 2)
					{
						array_push($searchWords, $keyword);
					}
					else
					{
						array_push($ignoredWords, $keyword);
					}
				}
			}
			
			$matchClause = '';
			$matchCond = ' AND ';
			if (count($searchWords) > 0)
			{
				$matchClause .= ' AND (';
				$ordWord = 0;
				foreach ($searchWords as $word)
				{
					if (++$ordWord > 1)
					{
						$matchClause .= $matchCond;
					}
					$normalizedWord = Searcher::normalizePartNumber($word);
					$cleanWord = Searcher::cleanSqlWord($word);
					$matchClause .= "(`ph_title` LIKE '%$cleanWord%' OR `ph_keywords` LIKE '%$cleanWord%'";
					if (strlen($normalizedWord) > 2)
					{
						$matchClause .= " OR `ph_match_part` LIKE '%$normalizedWord%' OR `ph_match_alt_part` like '%$normalizedWord%'";
					}
					$matchClause .= ')';
				}
				$matchClause .= ')';
			}
			
			if (strlen(trim($matchClause)) == 0)
			{
				$matchClause = ' ';
			}
			
			return $matchClause;
		}
		
		public static function normalizePartNumber($word)
		{
			if (!is_string($word))
			{
				return '';
			}
			return str_replace('O', '0', preg_replace('/[^A-Z0-9]/', '', strtoupper($word)));
		}
		
		public static function cleanSqlWord($word)
		{
			if (!is_string($word))
			{
				return '';
			}
			return str_replace('_', '\_', str_replace('%', '\%', str_replace("'", "\\'", str_replace('\\', '\\\\', $word))));
		}
				
		public function renderSearchResults($company, $keywords, $online)
		{
		}
	}
?>
