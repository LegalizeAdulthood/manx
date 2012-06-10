<?php

define("DEFAULT_ROWS_PER_PAGE", 10);

interface IFormatter
{
	public function renderResultsBar($ignoredWords, $searchWords, $start, $end, $total);
	public function renderPageSelectionBar($start, $total, $rowsPerPage, $params);
	public function renderResultsPage($rows, $start, $end);
}

?>
