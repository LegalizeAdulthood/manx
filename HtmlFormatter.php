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
			/*
			if (scalar @ignored_words > 0) {
				print '<P CLASS="warning">Ignoring ', neat_quoted_list(@ignored_words),
					'. All search words must be at least three letters long.</P>';
			}
			*/
			print '<div class="resbar">';
			if (count($searchWords) > 0)
			{
				print 'Searching for ' . $this->neatQuotedList($searchWords) . '.';
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
