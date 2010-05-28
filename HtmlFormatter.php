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
		public function renderPageSelectionBar($start, $total, $rowsPerPage)
		{
			throw new Exception("renderPageSelectionBar: not implemented");
		}
		public function renderResultsPage($rows, $start, $end)
		{
			throw new Exception("renderResultsPage: not implemented");
		}
	}
?>
