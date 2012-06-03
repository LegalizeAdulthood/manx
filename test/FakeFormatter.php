<?php
	require_once 'IFormatter.php';

	class FakeFormatter implements IFormatter
	{
		public function __construct()
		{
			$this->renderResultsBarCalled = false;
			$this->renderPageSelectionBarCalled = false;
			$this->renderPageSelectionBarCallCount = 0;
			$this->renderResultsPageCalled = false;
		}
		public $renderResultsBarCalled;
		public $renderResultsBarLastIgnoredWords;
		public $renderResultsBarLastSearchWords;
		public $renderResultsBarLastStart;
		public $renderResultsBarLastEnd;
		public $renderResultsBarLastTotal;
		public function renderResultsBar($ignoredWords, $searchWords, $start, $end, $total)
		{
			$this->renderResultsBarCalled = true;
			$this->renderResultsBarLastIgnoredWords = $ignoredWords;
			$this->renderResultsBarLastSearchWords = $searchWords;
			$this->renderResultsBarLastStart = $start;
			$this->renderResultsBarLastEnd = $end;
			$this->renderResultsBarLastTotal = $total;
		}
		public $renderPageSelectionBarCalled;
		public $renderPageSelectionBarCallCount;
		public $renderPageSelectionBarLastStart;
		public $renderPageSelectionBarLastTotal;
		public $renderPageSelectionBarLastRowsPerPage;
		public $renderPageSelectionBarLastParams;
		public function renderPageSelectionBar($start, $total, $rowsPerPage, $params)
		{
			$this->renderPageSelectionBarCalled = true;
			$this->renderPageSelectionBarCallCount++;
			$this->renderPageSelectionBarLastStart = $start;
			$this->renderPageSelectionBarLastTotal = $total;
			$this->renderPageSelectionBarLastRowsPerPage = $rowsPerPage;
			$this->renderPageSelectionBarLastParams = $params;
		}
		public $renderResultsPageCalled;
		public $renderResultsPageLastRows;
		public $renderResultsPageLastStart;
		public $renderResultsPageLastEnd;
		public function renderResultsPage($rows, $start, $end)
		{
			$this->renderResultsPageCalled = true;
			$this->renderResultsPageLastRows = $rows;
			$this->renderResultsPageLastStart = $start;
			$this->renderResultsPageLastEnd = $end;
		}
	}
?>
