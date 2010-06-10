<?php
	require_once('IDatabase.php');
	require_once('IManxDatabase.php');

	class ManxDatabase implements IManxDatabase
	{
		public static function getInstanceForDatabase(IDatabase $db)
		{
			return new ManxDatabase($db);
		}
		private function __construct(IDatabase $db)
		{
			$this->_db = $db;
		}
		public function __destruct()
		{
			$this->_db = null;
		}
		private $_db;

		function getDocumentCount()
		{
			$rows = $this->_db->query("SELECT COUNT(*) FROM `PUB`")->fetch();
			return $rows[0];
		}

		function getOnlineDocumentCount()
		{
			$rows = $this->_db->query("SELECT COUNT(DISTINCT `pub`) FROM `COPY`")->fetch();
			return $rows[0];
		}

		function getSiteCount()
		{
			$rows = $this->_db->query("SELECT COUNT(*) FROM `SITE`")->fetch();
			return $rows[0];
		}

		public function getSiteList()
		{
			return $this->_db->query("SELECT `url`,`description`,`low` FROM `SITE` WHERE `live`='Y' ORDER BY `siteid`")->fetchAll();
		}

		public function getCompanyList()
		{
			return $this->_db->query("SELECT `id`,`name` FROM `COMPANY` WHERE `display` = 'Y' ORDER BY `sort_name`")->fetchAll();
		}
	}
?>
