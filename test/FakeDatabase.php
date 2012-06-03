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
			$this->queryCalledForStatement = array();
		}

		public $queryFakeResults;
		public $queryFakeResultsForQuery;
		public $queryLastStatement;
		public $queryCalled;
		public $queryCalledForStatement;
		public function query($statement)
		{
			$this->queryCalled = true;
			$this->queryLastStatement = $statement;
			if (array_key_exists($statement, $this->queryFakeResultsForQuery))
			{
				$this->queryCalledForStatement[$statement] = true;
				return $this->queryFakeResultsForQuery[$statement];
			}
			return $this->queryFakeResults;
		}

		public static function createResultRowsForColumns($columns, $data)
		{
			$rows = array();
			foreach ($data as $item)
			{
				$row = array();
				for ($i = 0; $i < count($columns); $i++)
				{
					$row[$columns[$i]] = $item[$i];
				}
				array_push($rows, $row);
			}
			return $rows;
		}

	}
?>
