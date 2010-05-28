<?php
	require_once 'IFormatter.php';
	
	class HtmlFormatter implements IFormatter
	{
		public static function getInstance()
		{
			return new HtmlFormatter();
		}
		private function __construct()
		{
		}
		
		public static function neatQuotedList($words)
		{
			if (count($words) > 1)
			{
				return '"' . implode('", "', array_slice($words, 0, count($words) - 1))
					. '" and "' . $words[count($words) - 1] . '"';
			}
			else
			{
				return '"' . $words[0] . '"';
			}
		}
		
		public function renderResultsBar($ignoredWords, $searchWords, $start, $end, $total)
		{
			if (count($ignoredWords) > 0)
			{
				print '<p class="warning">Ignoring ' . HtmlFormatter::neatQuotedList($ignoredWords)
					. '.  All search words must be at least three letters long.</p>';
			}
			print '<div class="resbar">';
			if (count($searchWords) > 0)
			{
				print 'Searching for ' . HtmlFormatter::neatQuotedList($searchWords) . '.';
			}
			else
			{
				print 'Showing all documents.';
			}
			print ' Results <b>' . $start . ' - ' . $end . '</b> of <b>' . $total . '</b>.</div>';
		}
		
		public function renderPageSelectionBar($start, $total, $rowsPerPage, $params)
		{
			--$start;
			$encodedQuery = urlencode(array_key_exists('q', $params) ? $params['q'] : '');
			print '<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;';
			$linkOptions = ($rowsPerPage != DEFAULT_ROWS_PER_PAGE ? ';num=' . $rowsPerPage : '')
				. (array_key_exists('debug', $params) ? ';debug=1' : '')
				. (array_key_exists('disposition', $params) ? ';disposition=1' : '')
				. (array_key_exists('on', $params) && ($params['on'] == 'on') ? ';on=on' : '')
				. (array_key_exists('cp', $params) ? ';cp=' . $params['cp'] : '');
			if ($start != 0)
			{
				print '<a href="search.php?q=' . $encodedQuery . ';start='
					. ($start - $rowsPerPage) . $linkOptions . '"><b>Previous</b></a>&nbsp;&nbsp;';
			}

			$firstPage = intval($start /(10*$rowsPerPage))*10 + 1;
			$lastPageNum = intval(($total + $rowsPerPage - 1)/$rowsPerPage);
			$lastPageStart = ($lastPageNum - 1)*$rowsPerPage;
			$currPageNum = $firstPage;
			$currPageStart = ($currPageNum - 1)*$rowsPerPage;
			$startPageNum = intval($start/$rowsPerPage) + 1;

			$numIndices = 0;
			while ($numIndices++ < 10)
			{
				if ($start == $currPageStart)
				{
					print '<b class="currpage">' . $currPageNum . '</b>&nbsp;&nbsp;';
				}
				else
				{
					print '<a class="navpage" href="search.php?q='
						. $encodedQuery . ';start=' . $currPageStart . $linkOptions
						. '">' . $currPageNum . '</a>&nbsp;&nbsp;';
				}
				++$currPageNum;
				$currPageStart += $rowsPerPage;
				if ($currPageStart > $lastPageStart)
				{
					break;
				}
			}
			if ($start != $lastPageStart)
			{
				print '<a href="search.php?q=' . $encodedQuery
					. ';start=' . ($start + $rowsPerPage) . $linkOptions . '"><b>Next</b></a>';
			}
			print '</div>';
		}
		
		public function renderResultsPage($rows, $start, $end)
		{
			throw new Exception("renderResultsPage: not implemented");
		}
	}
?>
