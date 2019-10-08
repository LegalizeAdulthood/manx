<?php

define("DEFAULT_ROWS_PER_PAGE", 10);

interface IFormatter
{
    public function renderResultsBar(array $ignoredWords, array $searchWords, int $start, int $end, int $total);
    public function renderPageSelectionBar(int $start, int $total, int $rowsPerPage, array $params);
    public function renderResultsPage(array $rows, int $start, int $end);
}
