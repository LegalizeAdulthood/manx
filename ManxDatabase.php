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

		private function fetch($query)
		{
			return $this->_db->query($query)->fetch();
		}
		
		private function fetchAll($query)
		{
			return $this->_db->query($query)->fetchAll();
		}
		
		function getDocumentCount()
		{
			$rows = $this->fetch("SELECT COUNT(*) FROM `PUB`");
			return $rows[0];
		}

		function getOnlineDocumentCount()
		{
			$rows = $this->fetch("SELECT COUNT(DISTINCT `pub`) FROM `COPY`");
			return $rows[0];
		}

		function getSiteCount()
		{
			$rows = $this->fetch("SELECT COUNT(*) FROM `SITE`");
			return $rows[0];
		}

		public function getSiteList()
		{
			return $this->fetchAll("SELECT `url`,`description`,`low` FROM `SITE` WHERE `live`='Y' ORDER BY `siteid`");
		}

		public function getCompanyList()
		{
			return $this->fetchAll("SELECT `id`,`name` FROM `COMPANY` WHERE `display` = 'Y' ORDER BY `sort_name`");
		}
		
		public function getDisplayLanguage($languageCode)
		{
			// Avoid second name of language, if provided (after ';')
			$query = "SELECT IF(LOCATE(';',`eng_lang_name`),LEFT(`eng_lang_name`,LOCATE(';',`eng_lang_name`)-1),`eng_lang_name`) FROM `LANGUAGE` WHERE `lang_alpha_2`='%s'";
			return $this->fetch(sprintf($query, $languageCode));
		}
		
		public function getOSTagsForPub($pubId)
		{
			$query = sprintf("SELECT `tag_text` FROM `TAG`,`PUBTAG` WHERE `TAG`.`id`=`PUBTAG`.`tag` AND `TAG`.`class`='os' AND `pub`=%d", $pubId);
			$tags = array();
			foreach ($this->fetchAll($query) as $tagRow)
			{
				array_push($tags, trim($tagRow['tag_text']));
			}
			return $tags;
		}
		
		public function getAmendmentsForPub($pubId)
		{
			return $this->fetchAll(sprintf("SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`,`ph_pubdate` "
				. "FROM `PUB` JOIN `PUBHISTORY` ON `pub_id` = `ph_pub` WHERE `ph_amend_pub`=%d ORDER BY `ph_amend_serial`",
				$pubId));
		}
		
		public function getLongDescriptionForPub($pubId)
		{
			$description = array();
			/*
			TODO: LONG_DESC table missing
			$query = sprintf("SELECT 'html_text' FROM `LONG_DESC` WHERE `pub`=%d ORDER BY `line`", $pubId);
			foreach ($this->_db->query($query)->fetchAll() as $row)
			{
				array_push($description, $row['html_text']);
			}
			*/
			return $description;
		}
		
		public function getCitationsForPub($pubId)
		{
			$query = sprintf("SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`"
				. " FROM `CITEPUB` `C`"
				. " JOIN `PUB` ON (`C`.`pub`=`pub_id` AND `C`.`mentions_pub`=%d)"
				. " JOIN `PUBHISTORY` ON `pub_history`=`ph_id`", $pubId);
			return $this->fetchALl($query);
		}
		
		public function getTableOfContentsForPub($pubId, $fullContents)
		{
			$query = sprintf("SELECT `level`,`label`,`name` FROM `TOC` WHERE `pub`=%d", $pubId);
			if (!$fullContents)
			{
				$query .= ' AND `level` < 2';
			}
			$query .= ' ORDER BY `line`';
			return $this->fetchAll($query);
		}
		
		public function getMirrorsForCopy($copyId)
		{
			$query = sprintf("SELECT REPLACE(`url`,`original_stem`,`copy_stem`) AS `mirror_url`"
					. " FROM `COPY` JOIN `mirror` ON `COPY`.`site`=`mirror`.`site`"
					. " WHERE `copyid`=%d ORDER BY `rank` DESC", $copyId);
			$mirrors = array();
			foreach ($this->fetchAll($query) as $row)
			{
				array_push($mirrors, $row['mirror_url']);
			}
			return $mirrors;
		}
		
		public function getAmendedPub($pubId, $amendSerial)
		{
			$query = sprintf("SELECT `ph_company`,`pub_id`,`ph_part`,`ph_title`,`ph_pubdate`"
					. " FROM `PUB` JOIN `PUBHISTORY` ON `pub_history`=`ph_id`"
					. " WHERE `ph_amend_pub`=%d AND `ph_amend_serial`=%d",
				$pubId, $amendSerial);
			return $this->fetch($query);
		}
		
		public function getCopiesForPub($pubId)
		{
			$query = sprintf("SELECT `format`,`COPY`.`url`,`notes`,`size`,"
				. "`SITE`.`name`,`SITE`.`url` AS `site_url`,`SITE`.`description`,"
				. "`SITE`.`copy_base`,`SITE`.`low`,`COPY`.`md5`,`COPY`.`amend_serial`,"
				. "`COPY`.`credits`,`copyid`"
				. " FROM `COPY`,`SITE`"
				. " WHERE `COPY`.`site`=`SITE`.`siteid` AND `pub`=%d"
				. " ORDER BY `SITE`.`display_order`,`SITE`.`siteid`", $pubId);
			return $this->fetchAll($query);
		}
		
		public function getDetailsForPub($pubId)
		{
			$query = sprintf('SELECT `pub_id`, `COMPANY`.`name`, '
					. 'IFNULL(`ph_part`, "") AS `ph_part`, `ph_pubdate`, '
					. '`ph_title`, `ph_abstract`, '
					. 'IFNULL(`ph_revision`, "") AS `ph_revision`, `ph_ocr_file`, '
					. '`ph_cover_image`, `ph_lang`, `ph_keywords` '
					. 'FROM `PUB` '
					. 'JOIN `PUBHISTORY` ON `pub_history`=`ph_id` '
					. 'JOIN `COMPANY` ON `ph_company`=`COMPANY`.`id` '
					. 'WHERE %s AND `pub_id`=%d',
				'1=1', $pubId);
			return $this->fetch($query);
		}
		
		public static function normalizePartNumber($word)
		{
			if (!is_string($word))
			{
				return '';
			}
			return str_replace('O', '0', preg_replace('/[^A-Z0-9]/', '', strtoupper($word)));
		}

		public static function cleanSqlWord($word)
		{
			if (!is_string($word))
			{
				return '';
			}
			return str_replace('_', '\_', str_replace('%', '\%', str_replace("'", "\\'", str_replace('\\', '\\\\', $word))));
		}

		public static function matchClauseForSearchWords($searchWords)
		{
			$matchClause = '';
			$matchCond = ' AND ';
			if (count($searchWords) > 0)
			{
				$matchClause .= ' AND (';
				$ordWord = 0;
				foreach ($searchWords as $word)
				{
					if (++$ordWord > 1)
					{
						$matchClause .= $matchCond;
					}
					$normalizedWord = ManxDatabase::normalizePartNumber($word);
					$cleanWord = ManxDatabase::cleanSqlWord($word);
					$matchClause .= "(`ph_title` LIKE '%$cleanWord%' OR `ph_keywords` LIKE '%$cleanWord%'";
					if (strlen($normalizedWord) > 2)
					{
						$matchClause .= " OR `ph_match_part` LIKE '%$normalizedWord%' OR `ph_match_alt_part` LIKE '%$normalizedWord%'";
					}
					$matchClause .= ')';
				}
				$matchClause .= ')';
			}

			if (strlen(trim($matchClause)) == 0)
			{
				$matchClause = ' ';
			}

			return $matchClause;
		}

		public function searchForPublications($company, $keywords, $online)
		{
			$matchClause = $this->matchClauseForKeywords($keywords);
			$onlineClause = $online ? "`pub_has_online_copies`" : '1=1';
			$mainQuery = "SELECT `pub_id`, `ph_part`, `ph_title`,"
				. " `pub_has_online_copies`, `ph_abstract`, `pub_has_toc`,"
				. " `pub_superseded`, `ph_pubdate`, `ph_revision`,"
				. " `ph_company`, `ph_alt_part`, `ph_pubtype` FROM `PUB`"
				. " JOIN `PUBHISTORY` ON `pub_history` = `ph_id`"
				. " WHERE $onlineClause $matchClause"
				. " AND `ph_company`=$company"
				. " ORDER BY `ph_sort_part`, `ph_pubdate`, `pub_id`";
			$rows = $this->_db->query($mainQuery)->fetchAll();
		}
	}
?>
