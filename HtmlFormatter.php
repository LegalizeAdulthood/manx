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
			throw new Exception("renderResultsBar: not implemented");
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
