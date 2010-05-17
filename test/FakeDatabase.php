<?php
	require_once 'IDatabase.php';

	class FakeDatabase implements IDatabase
	{
		public function __construct()
		{
			$queryCalled = false;
			$queryFakeResults = array();
			$queryLastStatement = '';
		}
		
		public $queryFakeResults;
		public $queryLastStatement;
		public $queryCalled;
		
		public function query($statement)
		{
			$this->queryCalled = true;
			$this->queryLastStatement = $statement;
			return $this->queryFakeResults;
		}
	}	
?>
