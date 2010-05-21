<?php
	require_once 'IDatabase.php';
	require_once 'ISearcher.php';
	
	class Searcher implements ISearcher
	{
		private $_db;

		public static function getInstance(IDatabase $db)
		{
			return new Searcher($db);
		}
		
		private function __construct($db)
		{
			$this->_db = $db;
		}
		
		public function renderDefaultCompanies()
		{
			print '<select id="CP" name="cp">';
			$defaultId = 1; // Digital Equipment Corporation
			foreach ($this->_db->query("SELECT `id`,`name` FROM `COMPANY` ORDER BY `sort_name`") as $row)
			{
				$id = $row['id'];
				print '<option value="' . $id . '"' . ($id == $defaultId ? ' selected' : '') . '>' . htmlspecialchars($row['name']) . '</option>';
			}
			print '</select>';
		}
	}
?>
