<?php
	require_once 'IDatabase.php';

	class FakeDatabase implements IDatabase
	{
		public function __construct()
		{
			$this->queryCalled = false;
			$this->queryFakeResults = array();
			$this->queryFakeResultsForQuery = array();
			$this->queryLastStatement = '';
		}
		
		public $queryFakeResults;
		public $queryFakeResultsForQuery;
		public $queryLastStatement;
		public $queryCalled;
		
		public function query($statement)
		{
			$this->queryCalled = true;
			$this->queryLastStatement = $statement;
			if (array_key_exists($statement, $this->queryFakeResultsForQuery))
			{
				return $this->queryFakeResultsForQuery[$statement];
			}
			return $this->queryFakeResults;
		}
	}	
?>
