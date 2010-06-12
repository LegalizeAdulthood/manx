<?php
	require_once 'IDatabase.php';
	require_once 'ISearcher.php';
	require_once 'IFormatter.php';
	require_once 'IManxDatabase.php';

	class Searcher implements ISearcher
	{
		private $_searchWords;
		private $_ignoredWords;
		private $_db;
		private $_manxDb;

		public static function getInstance(IDatabase $db, IManxDatabase $manxDb)
		{
			return new Searcher($db, $manxDb);
		}

		private function __construct(IDatabase $db, IManxDatabase $manxDb)
		{
			$this->_db = $db;
			$this->_manxDb = $manxDb;
		}

		public function renderCompanies($selected)
		{
			print '<select id="CP" name="cp">';
			foreach ($this->_manxDb->getCompanyList() as $row)
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

		public static function filterSearchKeywords($keywords, &$ignoredWords)
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
			return $searchWords;
		}
		
		public function matchClauseForKeywords($keywords)
		{
			$this->_searchWords = Searcher::filterSearchKeywords($keywords, $this->_ignoredWords);

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
					$normalizedWord = ManxDatabase::normalizePartNumber($word);
					$cleanWord = ManxDatabase::cleanSqlWord($word);
					$matchClause .= "(`ph_title` LIKE '%$cleanWord%' OR `ph_keywords` LIKE '%$cleanWord%'";
					if (strlen($normalizedWord) > 2)
					{
						$matchClause .= " OR `ph_match_part` LIKE '%$normalizedWord%' OR `ph_match_alt_part` LIKE '%$normalizedWord%'";
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

		public function renderSearchResults(IFormatter $formatter, $company, $keywords, $online)
		{
			$params = Searcher::parameterSource($_GET, $_POST);
			$stmt = '';
			$rows = array();
			$matchClause = $this->matchClauseForKeywords($keywords);
			$onlineClause = $online ? "`pub_has_online_copies`" : '1=1';
			$mainQuery = "SELECT `pub_id`, `ph_part`, `ph_title`,"
				. " `pub_has_online_copies`, `ph_abstract`, `pub_has_toc`,"
				. " `pub_superseded`, `ph_pubdate`, `ph_revision`,"
				. " `ph_company`, `ph_alt_part`, `ph_pubtype` FROM `PUB`"
				. " JOIN `PUBHISTORY` ON `pub_history` = `ph_id`"
				. " WHERE $onlineClause $matchClause"
				. " AND `ph_company`=$company"
				. " ORDER BY `ph_sort_part`, `ph_pubdate`, `pub_id`";
			$rows = $this->_db->query($mainQuery)->fetchAll();
			$total = count($rows);
			if (array_key_exists('start', $params))
			{
				$start = $params['start'] - 1;
			}
			else
			{
				$start = 0;
			}
			$rowsPerPage = DEFAULT_ROWS_PER_PAGE;
			$end = min($total - 1, $start + $rowsPerPage - 1);
			for ($i = $start; $i <= $end; $i++)
			{
				$tags = $this->_manxDb->getOSTagsForPub($rows[$i]['pub_id']);
				$rows[$i]['tags'] = $tags;
			}
			$formatter->renderResultsBar($this->_ignoredWords, $this->_searchWords, $start, $end, $total);
			$formatter->renderPageSelectionBar($start, $total, $rowsPerPage, $params);
			$formatter->renderResultsPage($rows, $start, $end);
			$formatter->renderPageSelectionBar($start, $total, $rowsPerPage, $params);
		}
	}
?>
