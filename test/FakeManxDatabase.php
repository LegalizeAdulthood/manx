<?php
	require_once 'IManxDatabase.php';

	class FakeManxDatabase implements IManxDatabase
	{
		public function __construct()
		{
			$this->getSiteListCalled = false;
			$this->getCompanyListCalled = false;
		}

		public $getDocumentCountCalled, $getDocumentCountFakeResult;
		public function getDocumentCount()
		{
			$this->getDocumentCountCalled = true;
			return $this->getDocumentCountFakeResult;
		}

		public $getOnlineDocumentCountCalled, $getOnlineDocumentCountFakeResult;
		public function getOnlineDocumentCount()
		{
			$this->getOnlineDocumentCountCalled = true;
			return $this->getOnlineDocumentCountFakeResult;
		}

		public $getSiteCountCalled, $getSiteCountFakeResult;
		public function getSiteCount()
		{
			$this->getSiteCountCalled = true;
			return $this->getSiteCountFakeResult;
		}

		public $getSiteListCalled, $getSiteListFakeResult;
		public function getSiteList()
		{
			$this->getSiteListCalled = true;
			return $this->getSiteListFakeResult;
		}

		public $getCompanyListCalled, $getCompanyListFakeResult;
		public function getCompanyList()
		{
			$this->getCompanyListCalled = true;
			return $this->getCompanyListFakeResult;
		}
	}
?>
