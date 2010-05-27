<?php
	interface IFormatter
	{
		public function renderResultsBar($ignoredWords, $searchWords, $start, $end, $total);
		public function renderPageSelectionBar($start, $total, $rowsPerPage);
		public function renderResultsPage($rows, $start, $end);
	}
?>
