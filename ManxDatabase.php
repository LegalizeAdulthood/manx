<?php

require_once('IDatabase.php');
require_once('IManxDatabase.php');

class Company
{
	const DEC = 1;
	const TI = 2;
	const TeleVideo = 6;
	const Visual = 9;
	const Wyse = 13;
	const IBM = 19;
	const Motorola = 49;
	const Interdata_PerkinElmer = 58;
	const Teletype = 70;
	const GRI = 80;
}

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

	private function execute($statement, $args)
	{
		return $this->_db->execute($statement, $args);
	}

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
		$rows = $this->fetch("SELECT COUNT(*) FROM `pub`");
		return $rows[0];
	}

	function getOnlineDocumentCount()
	{
		$rows = $this->fetch("SELECT COUNT(DISTINCT `pub`) FROM `copy`");
		return $rows[0];
	}

	function getSiteCount()
	{
		$rows = $this->fetch("SELECT COUNT(*) FROM `site`");
		return $rows[0];
	}

	public function getSiteList()
	{
		return $this->fetchAll("SELECT `url`,`description`,`low` FROM `site` WHERE `live`='Y' ORDER BY `siteid`");
	}

	public function getCompanyList()
	{
		return $this->fetchAll("SELECT `id`,`name` FROM `company` WHERE `display` = 'Y' ORDER BY `sort_name`");
	}

	public function getDisplayLanguage($languageCode)
	{
		// Avoid second name of language, if provided (after ';')
		$query = "SELECT IF(LOCATE(';',`eng_lang_name`),LEFT(`eng_lang_name`,LOCATE(';',`eng_lang_name`)-1),`eng_lang_name`) FROM `language` WHERE `lang_alpha_2`='%s'";
		return $this->fetch(sprintf($query, $languageCode));
	}

	public function getOSTagsForPub($pubId)
	{
		$query = sprintf("SELECT `tag_text` FROM `tag`,`pub_tag` WHERE `tag`.`id`=`pub_tag`.`tag` AND `tag`.`class`='os' AND `pub`=%d", $pubId);
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
			. "FROM `pub` JOIN `pub_history` ON `pub_id` = `ph_pub` WHERE `ph_amend_pub`=%d ORDER BY `ph_amend_serial`",
			$pubId));
	}

	public function getLongDescriptionForPub($pubId)
	{
		$description = array();
		/*
		TODO: LONG_DESC table missing
		$query = sprintf("SELECT 'html_text' FROM `long_desc` WHERE `pub`=%d ORDER BY `line`", $pubId);
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
			. " FROM `cite_pub` `C`"
			. " JOIN `pub` ON (`C`.`pub`=`pub_id` AND `C`.`mentions_pub`=%d)"
			. " JOIN `pub_history` ON `pub`.`pub_history`=`ph_id`", $pubId);
		return $this->fetchAll($query);
	}

	public function getTableOfContentsForPub($pubId, $fullContents)
	{
		$query = sprintf("SELECT `level`,`label`,`name` FROM `toc` WHERE `pub`=%d", $pubId);
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
				. " FROM `copy` JOIN `mirror` ON `copy`.`site`=`mirror`.`site`"
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
				. " FROM `pub` JOIN `pub_history` ON `pub`.`pub_history`=`ph_id`"
				. " WHERE `ph_amend_pub`=%d AND `ph_amend_serial`=%d",
			$pubId, $amendSerial);
		return $this->fetch($query);
	}

	public function getCopiesForPub($pubId)
	{
		$query = sprintf("SELECT `format`,`copy`.`url`,`notes`,`size`,"
			. "`site`.`name`,`site`.`url` AS `site_url`,`site`.`description`,"
			. "`site`.`copy_base`,`site`.`low`,`copy`.`md5`,`copy`.`amend_serial`,"
			. "`copy`.`credits`,`copyid`"
			. " FROM `copy`,`site`"
			. " WHERE `copy`.`site`=`site`.`siteid` AND `pub`=%d"
			. " ORDER BY `site`.`display_order`,`site`.`siteid`", $pubId);
		return $this->fetchAll($query);
	}

	public function getDetailsForPub($pubId)
	{
		$query = sprintf('SELECT `pub_id`, `company`.`name`, '
				. 'IFNULL(`ph_part`, "") AS `ph_part`, `ph_pubdate`, '
				. '`ph_title`, `ph_abstract`, '
				. 'IFNULL(`ph_revision`, "") AS `ph_revision`, `ph_ocr_file`, '
				. '`ph_cover_image`, `ph_lang`, `ph_keywords` '
				. 'FROM `pub` '
				. 'JOIN `pub_history` ON `pub`.`pub_history`=`ph_id` '
				. 'JOIN `company` ON `ph_company`=`company`.`id` '
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

	public static function stripNonAlphaNumeric($text)
	{
		return preg_replace('/[^A-Z0-9]/', '', $text);
	}

	public static function sortPartNumberDEC($pn)
	{
		$pn = preg_replace('/-PRE\d*$/', '-000', $pn);
		$pn = ManxDatabase::stripNonAlphaNumeric($pn);
		// special case to get RT-11 Software Dispatch in order
		if (preg_match('/^ADC740.B.$/', $pn, $matches))
		{
			$pn = preg_replace('/B(\d)$/', "0\\1", $pn);
		}
		return $pn;
	}

	public static function sortPartNumberTI($pn)
	{
		if (preg_match('/^\d{6}-\d{4}/', $pn))
		{
			$pn = '0' . $pn;
		}
		return ManxDatabase::stripNonAlphaNumeric($pn);
	}

	public static function sortPartNumberTeleVideo($pn)
	{
		// B300013-001 was earliest form. Then dropped B and then added a '1'.
		// 970 Maintenance Manual has numbers 3002100 (transitional), and then 130021-00
		if (substr($pn, 0, 1) == 'B')
		{
			$pn = substr($pn, 1);
		}
		if (substr($pn, 0, 1) == '3')
		{
			$pn = '0' . $pn;
		}
		return ManxDatabase::stripNonAlphaNumeric($pn);
	}

	public static function sortPartNumberVisual($pn)
	{
		// Order by numbers only
		$pn = preg_replace('/[^0-9]/', '', $pn);
		return $pn;
	}

	public static function sortPartNumberWyse($pn)
	{
		// An extra digit was added in the middle in 1985, so pad earlier numbers
		if (preg_match('/^\d\d-\d\d\d-/', $pn))
		{
			$pn = substr($pn, 0, 3) . '0' . substr($pn, 3);
		}
		return ManxDatabase::stripNonAlphaNumeric($pn);
	}

	public static function sortPartNumberIBM($pn)
	{
		if (preg_match('/\d\d-\d\d\d\d$/', $pn))
		{
			$pn .= '-0';
		}
		if (preg_match('/^\d\d-\d\d\d\d-\d+$/', $pn))
		{
			$pn = 'A' . $pn;
		}
		if (preg_match('/^[A-Z]\w\w\d-/', $pn))
		{
			$pn = substr($pn, 1);
		}
		if (preg_match('/^\w\w\d-\d\d\d\d-(\d+)$/', $pn, $matches))
		{
			$pn = substr($pn, 0, 9) . sprintf("%02d", $matches[1]);
		}
		return ManxDatabase::stripNonAlphaNumeric($pn);
	}

	public static function sortPartNumberMotorola($pn)
	{
		if (preg_match('/AN(\d+)(.*)/', $pn, $matches))
		{
			$pn = sprintf("AN%05d%s", $matches[1], $matches[2]);
		}
		return $pn;
	}

	public static function sortPartNumberInterdata($pn)
	{
		// initial letters (distribution codes, like IBM's?) disregarded
		if (preg_match('/^([A-Z]+)/', $pn, $matches))
		{
			$pn = substr($pn, strlen($matches[1]));
		}
		return ManxDatabase::stripNonAlphaNumeric($pn);
	}

	public static function sortPartNumberTeletype($pn)
	{
		if (preg_match('/^(\d+)(.*)/', $pn, $matches))
		{
			$pn = sprintf("%04d%s", $matches[1], $matches[2]);
		}
		return ManxDatabase::stripNonAlphaNumeric($pn);
	}

	public static function sortPartNumberGRI($pn)
	{
		if (preg_match('/^(\d\d)-(\d\d)-(.*)$/', $pn, $matches))
		{
			$pn = $matches[1] . sprintf("%03d", $matches[2]) . $matches[3];
		}
		return ManxDatabase::stripNonAlphaNumeric($pn);
	}

	public static function sortPartNumber($company, $pn)
	{
		$pn = strtoupper($pn);
		// Calculate a default sorted part number, along the same lines as normalization, without the 'O' -> '0' translation
		$spn = ManxDatabase::stripNonAlphaNumeric($pn);
		switch ($company)
		{
		case Company::DEC:			return ManxDatabase::sortPartNumberDEC($pn);
		case Company::TI:			return ManxDatabase::sortPartNumberTI($pn);
		case Company::TeleVideo:	return ManxDatabase::sortPartNumberTeleVideo($pn);
		case Company::Visual:		return ManxDatabase::sortPartNumberVisual($pn);
		case Company::Wyse:			return ManxDatabase::sortPartNumberWyse($pn);
		case Company::IBM:			return ManxDatabase::sortPartNumberIBM($pn);
		case Company::Motorola:		return ManxDatabase::sortPartNumberMotorola($pn);
		case Company::Interdata_PerkinElmer: return ManxDatabase::sortPartNumberInterdata($pn);
		case Company::Teletype:		return ManxDatabase::sortPartNumberTeletype($pn);
		case Company::GRI:			return ManxDatabase::sortPartNumberGRI($pn);
		}
		return $spn;
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
		$matchClause = ManxDatabase::matchClauseForSearchWords($keywords);
		$onlineClause = $online ? "`pub_has_online_copies`" : '1=1';
		$query = "SELECT `pub_id`, `ph_part`, `ph_title`,"
				. " `pub_has_online_copies`, `ph_abstract`, `pub_has_toc`,"
				. " `pub_superseded`, `ph_pubdate`, `ph_revision`,"
				. " `ph_company`, `ph_alt_part`, `ph_pubtype`"
			. " FROM `pub`"
			. " JOIN `pub_history` ON `pub`.`pub_history` = `ph_id`"
			. " WHERE $onlineClause $matchClause"
			. " AND `ph_company`=$company"
			. " ORDER BY `ph_sort_part`, `ph_pubdate`, `pub_id`";
		return $this->fetchAll($query);
	}

	function getPublicationsSupersededByPub($pubId)
	{
		$query = sprintf('SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` FROM `supersession`' .
			' JOIN `pub` ON (`old_pub`=`pub_id` AND `new_pub`=%d)' .
			' JOIN `pub_history` ON `pub_history`=`ph_id`', $pubId);
		return $this->fetchAll($query);
	}

	function getPublicationsSupersedingPub($pubId)
	{
		$query = sprintf('SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` FROM `supersession`'
			. ' JOIN `pub` ON (`new_pub`=`pub_id` AND `old_pub`=%d)'
			. ' JOIN `pub_history` ON `pub_history`=`ph_id`', $pubId);
		return $this->fetchAll($query);
	}

	function getUserId($email, $pw_sha1)
	{
		$rows = $this->execute("SELECT `id` FROM `user` WHERE `email`=? AND `pw_sha1`=?",
			array($email, $pw_sha1));
		return (count($rows) > 0) ? $rows[0]['id'] : -1;
	}

	function createSessionForUser($userId, $sessionId, $remoteHost, $userAgent)
	{
		$this->execute("DELETE FROM `user_session` WHERE `user_id`=?", array($userId));
		$this->execute("INSERT INTO `user_session`" 
				. "(`user_id`, `logged_in`, `ascii_session_id`, `created`, `user_agent`) "
				. "VALUES (?, 1, ?, NOW(), ?)",
			array($userId, $sessionId, $userAgent));
	}

	function deleteUserSession($sessionId)
	{
		$this->execute("DELETE FROM `user_session` WHERE `ascii_session_id`=?",
			array($sessionId));
	}

	function getUserFromSessionId($sessionId)
	{
		$rows = $this->execute("SELECT `user_session`.`user_id`, "
					. "`user_session`.`logged_in`, "
					. "`user_session`.`last_impression`, "
					. "`user`.`first_name`, `user`.`last_name` "
				. "FROM `user`, `user_session` "
				. "WHERE `ascii_session_id`=? and "
					. "`user_session`.`user_id`=`user`.`id`",
			array($sessionId));
		return (count($rows) == 1) ? $rows[0] : array();
	}

	function getPublicationsForPartNumber($part, $company)
	{
		$part = "%" . ManxDatabase::normalizePartNumber($part) . "%";
		return $this->_db->execute("SELECT pub_id,ph_part,ph_title "
				. "FROM pub JOIN pub_history ON pub_history = ph_id "
				. "WHERE (ph_match_part LIKE ? OR ph_match_alt_part LIKE ?) AND ph_company = ? "
				. "ORDER BY ph_sort_part, ph_pubdate LIMIT 10",
			array($part, $part, $company));
	}

	// Add pub_history row, with ph_pub = 0
	function addPubHistory($userId, $publicationType, $company, $part, 
		$altPart, $revision, $pubDate, $title, $keywords, $notes, $languages)
	{
		$this->_db->execute(
			'INSERT INTO `pub_history`(`ph_created`, `ph_edited_by`, `ph_pub`, '
				. '`ph_pubtype`, `ph_company`, `ph_part`, `ph_alt_part`, '
				. '`ph_revision`, `ph_pubdate`, `ph_title`, `ph_keywords`, '
				. '`ph_notes`, `ph_lang`, '
				. '`ph_match_part`, `ph_match_alt_part`, `ph_sort_part`) '
			. 'VALUES (now(), ?, 0, '
				. '?, ?, ?, ?, '
				. '?, ?, ?, ?, '
				. '?, ?, '
				. '?, ?, ?)',
			array($userId,
				$publicationType, $company, $part, $altPart,
				$revision, $pubDate, $title, $keywords,
				$notes, $languages,
				ManxDatabase::normalizePartNumber($part),
					ManxDatabase::normalizePartNumber($altPart),
					ManxDatabase::sortPartNumber($company, $part)
				));
		return $this->_db->getLastInsertId();
	}

	// Add pub row, with pub_history = ph_id
	function addPublication($pubHistoryId)
	{
		$this->_db->execute('INSERT INTO `pub` (`pub_history`) VALUES (?)',
			array($pubHistoryId));
		return $this->_db->getLastInsertId();
	}

	// Update pub_history row, with ph_pub = pub_id
	function updatePubHistoryPubId($pubHistoryId, $pubId)
	{
		$this->_db->execute('UPDATE `pub_history` SET `ph_pub` = ? WHERE `ph_id` = ?',
			array($pubId, $pubHistoryId));
	}

	function getCompanyForId($id)
	{
		$rows = $this->_db->execute('SELECT * FROM `company` WHERE `id`=?', array($id));
		return (count($rows) > 0) ? $rows[0] : array();
	}

	function addCompany($fullName, $shortName, $sortName, $display, $notes)
	{
		$this->_db->execute('INSERT INTO `company`(`name`,`shortname`,`sort_name`,`display`,`notes`) VALUES (?,?,?,?,?)',
			array($fullName, $shortName, $sortName, $display ? 'Y' : 'N', $notes));
		return $this->_db->getLastInsertId();
	}

	function updateCompany($id, $fullName, $shortName, $sortName, $display, $notes)
	{
		$this->_db->execute("UPDATE `company` " 
				. "SET `name`=?, `shortname`=?, `sort_name`=?, `display`=?, `notes`=? "
				. "WHERE `id`=?",
			array($name, $shortName, $sortName, $display ? 'Y' : 'N', $ntoes, $id));
	}

	function getMirrors()
	{
		return $this->fetchAll("SELECT * FROM `mirror` ORDER BY `site`,`rank`");
	}

	function getSites()
	{
		return $this->fetchAll("SELECT * FROM `site` ORDER BY `display_order`");
	}

	function getFormatForExtension($extension)
	{
		$rows = $this->execute("SELECT `format` FROM `format_extension` WHERE `extension`=?",
			array(strtolower($extension)));
		return (count($rows) > 0) ? $rows[0]['format'] : '';
	}

	function getCompanyForBitSaversDirectory($dir)
	{
		$rows = $this->execute("SELECT `company_id` FROM `company_bitsavers` WHERE `directory`=?",
			array($dir));
		return (count($rows) > 0) ? $rows[0]['company_id'] : -1;
	}

	function addSupersession($oldPub, $newPub)
	{
		$this->_db->execute('INSERT INTO `supersession`(`old_pub`,`new_pub`) VALUES (?,?)',
			array($oldPub, $newPub));
		return $this->_db->getLastInsertId();
	}

	function addSite($name, $url, $description, $copy_base, $low, $live)
	{
		$this->_db->execute('INSERT INTO `site`(`name`,`url`,`description`,`copy_base`,`low`,`live`) VALUES (?,?,?,?,?,?)',
			array($name, $url, $description, $copy_base, $low, $live));
		return $this->_db->getLastInsertId();
	}

	function addCopy($pubId, $format, $siteId, $url,
		$notes, $size, $md5, $credits, $amendSerial)
	{
		$this->_db->execute('INSERT INTO `copy`(`pub`,`format`,`site`,`url`,`notes`,`size`,`md5`,`credits`,`amend_serial`) '
			. 'VALUES (?,?,?,?,?,?,?,?,?)',
			array($pubId, $format, $siteId, $url, $notes, $size, $md5, $credits, $amendSerial));
		$result = $this->_db->getLastInsertId();
		$this->_db->execute('UPDATE `pub` SET `pub_has_online_copies`=1 WHERE `pub_id`=?', array($pubId));
		return $result;
	}

	function addBitSaversDirectory($companyId, $directory)
	{
		$row = $this->execute("SELECT FROM `company_bitsaver` WHERE `company_id`=?", array($companyId));
		if (count($row) == 0)
		{
			$this->_db->execute('INSERT INTO `company_bitsavers`(`company_id`,`directory`) VALUES (?,?)',
				array($companyId, $directory));
		}
	}

}

?>
