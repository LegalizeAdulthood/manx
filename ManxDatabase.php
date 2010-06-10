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
		
		public function getDisplayLanguage($languageCode)
		{
			// Avoid second name of language, if provided (after ';')
			$query = "SELECT IF(LOCATE(';',`eng_lang_name`),LEFT(`eng_lang_name`,LOCATE(';',`eng_lang_name`)-1),`eng_lang_name`) FROM `LANGUAGE` WHERE `lang_alpha_2`='%s'";
			return $this->_db->query(sprintf($query, $languageCode))->fetch();
		}
		
		public function getOSTagsForPub($pubId)
		{
			$query = sprintf("SELECT `tag_text` FROM `TAG`,`PUBTAG` WHERE `TAG`.`id`=`PUBTAG`.`tag` AND `TAG`.`class`='os' AND `pub`=%d", $pubId);
			$tags = array();
			foreach ($this->_db->query($query)->fetchAll() as $tagRow)
			{
				array_push($tags, trim($tagRow['tag_text']));
			}
			return $tags;
		}
	}
?>
