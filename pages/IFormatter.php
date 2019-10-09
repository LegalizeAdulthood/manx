<?php

define("DEFAULT_ROWS_PER_PAGE", 10);

interface IFormatter
{
    public function renderResultsBar(array $ignoredWords, array $searchWords, $start, $end, $total);
    public function renderPageSelectionBar($start, $total, $rowsPerPage, array $params);
    public function renderResultsPage(array $rows, $start, $end);
}
