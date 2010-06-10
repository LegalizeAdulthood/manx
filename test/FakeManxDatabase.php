<?php
	require_once 'IManxDatabase.php';

	class FakeManxDatabase implements IManxDatabase
	{
		public function __construct()
		{
			$this->getSiteListCalled = false;
		}
		
		public $getSiteListCalled, $getSiteListFakeResult;
		public function getSiteList()
		{
			$this->getSiteListCalled = true;
			return $this->getSiteListFakeResult;
		}
	}
?>
