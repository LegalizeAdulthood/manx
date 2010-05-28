<?php
	require_once 'IDatabase.php';
	require_once 'ISearcher.php';
	
	class Searcher implements ISearcher
	{
		private $_searchWords;
		private $_ignoredWords;
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
			$this->_searchWords = array();
			$this->_ignoredWords = array();
			foreach (explode(' ', $keywords) as $keyword)
			{
				$keyword = trim($keyword);
				if ($keyword != '')
				{
					if (strlen($keyword) > 2)
					{
						array_push($this->_searchWords, $keyword);
					}
					else
					{
						array_push($this->_ignoredWords, $keyword);
					}
				}
			}
			
			$matchClause = '';
			$matchCond = ' AND ';
			if (count($this->_searchWords) > 0)
			{
				$matchClause .= ' AND (';
				$ordWord = 0;
				foreach ($this->_searchWords as $word)
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
				
		public function renderSearchResults(IFormatter $formatter, $company, $keywords, $online)
		{
			$stmt = '';
			$rows = array();
			$matchClause = $this->matchClauseForKeywords($keywords);
			$rows = $this->_db->query("SELECT `pub_id`, `ph_part`, `ph_title`,"
				. " `pub_has_online_copies`, `ph_abstract`, `pub_has_toc`,"
				. " `pub_superseded`, `ph_pubdate`, `ph_revision`,"
				. " `ph_company`, `ph_alt_part`, `ph_pubtype` FROM `PUB`"
				. " JOIN `PUBHISTORY` ON `pub_history` = `ph_id`"
				. " WHERE `pub_has_online_copies` $matchClause"
				. " AND `ph_company`=$company"
				. " ORDER BY `ph_sort_part`, `ph_pubdate`, `pub_id`")->fetchAll();
			$total = count($rows);
			$params = $this->parameterSource($_GET, $_POST);
			if (array_key_exists('start', $params))
			{
				$start = $params['start'];
			}
			else
			{
				$start = 1;
			}
			$rowsPerPage = 10;
			$end = min($total, $start + $rowsPerPage - 1);
			$formatter->renderResultsBar($this->_ignoredWords, $this->_searchWords, $start, $end, $total);
			$formatter->renderPageSelectionBar($start, $total, $rowsPerPage);
			$formatter->renderResultsPage($rows, $start, $end);
			$formatter->renderPageSelectionBar($start, $total, $rowsPerPage);
		}
	}
?>
