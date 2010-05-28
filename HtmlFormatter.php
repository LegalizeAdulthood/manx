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
		
		public function renderResultsBar($ignoredWords, $searchWords, $start, $end, $total)
		{
			/*
			if (scalar @ignored_words > 0) {
				print '<P CLASS="warning">Ignoring ', neat_quoted_list(@ignored_words),
					'. All search words must be at least three letters long.</P>';
			}

			if (scalar @search_words) {
				print qq{<DIV CLASS="resbar">Searching for }, neat_quoted_list(@search_words), '.';
			} else {
				print qq{<DIV CLASS="resbar">Showing all documents.};
			}

			print qq{ Results <B>$start - $end</B> of <B>$total_matches</B>.</DIV>\n};
			*/
			print '<div class="resbar">Showing all documents.';
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
