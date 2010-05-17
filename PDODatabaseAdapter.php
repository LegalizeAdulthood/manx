<?php
	require_once 'IDatabase.php';
	
	class PDODatabaseAdapter implements IDatabase
	{
		public static function getInstance($pdo)
		{
			return new PDODatabaseAdapter($pdo);
		}
		private function __construct($pdo)
		{
			$_pdo = $pdo;
		}
		private $_pdo;

		public function query($statement)
		{
			return $_pdo->query($statement);
		}
	}
?>
