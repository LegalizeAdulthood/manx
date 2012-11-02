<?php

require_once 'ISearcher.php';
require_once 'IFormatter.php';
require_once 'IManxDatabase.php';

class Searcher implements ISearcher
{
	private $_searchWords;
	private $_ignoredWords;
	private $_manxDb;

	public static function getInstance(IManxDatabase $manxDb)
	{
		return new Searcher($manxDb);
	}

	private function __construct(IManxDatabase $manxDb)
	{
		$this->_manxDb = $manxDb;
	}

	public function renderCompanies($selected)
	{
		print '<select id="CP" name="cp">';
		foreach ($this->_manxDb->getCompanyList() as $row)
		{
			$id = $row['id'];
			print '<option value="' . $id . '"' . ($id == $selected ? ' selected="selected"' : '') . '>' . htmlspecialchars($row['name']) . '</option>';
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

	public function renderSearchResults(IFormatter $formatter, $company, $keywords, $online)
	{
		$params = Searcher::parameterSource($_GET, $_POST);
		$stmt = '';
		$rows = array();
		$this->_searchWords = Searcher::filterSearchKeywords($keywords, $this->_ignoredWords);
		$rows = $this->_manxDb->searchForPublications($company, $this->_searchWords, $online);
		$total = count($rows);
		if (array_key_exists('start', $params))
		{
			$start = $params['start'];
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
