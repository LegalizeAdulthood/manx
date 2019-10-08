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
    /**
     * @param IDatabase $db
     * @return IManxDatabase
     */
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

    private function beginTransaction()
    {
        $this->_db->beginTransaction();
    }

    private function commit()
    {
        $this->_db->commit();
    }

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
        return $this->fetchAll("SELECT `url`,`description`,`low` FROM `site` WHERE `live`='Y' ORDER BY `site_id`");
    }

    public function getCompanyList()
    {
        return $this->fetchAll("SELECT `id`,`name` FROM `company` WHERE `display` = 'Y' ORDER BY `sort_name`");
    }

    public function getDisplayLanguage(string $languageCode)
    {
        // Avoid second name of language, if provided (after ';')
        $query = "SELECT IF(LOCATE(';',`eng_lang_name`),LEFT(`eng_lang_name`,LOCATE(';',`eng_lang_name`)-1),`eng_lang_name`) FROM `language` WHERE `lang_alpha_2`='%s'";
        return $this->fetch(sprintf($query, $languageCode));
    }

    public function getOSTagsForPub(int $pubId)
    {
        $query = sprintf("SELECT `tag_text` FROM `tag`,`pub_tag` WHERE `tag`.`id`=`pub_tag`.`tag` AND `tag`.`class`='os' AND `pub`=%d", $pubId);
        $tags = array();
        foreach ($this->fetchAll($query) as $tagRow)
        {
            array_push($tags, trim($tagRow['tag_text']));
        }
        return $tags;
    }

    public function getAmendmentsForPub(int $pubId)
    {
        return $this->fetchAll(sprintf("SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`,`ph_pub_date` "
            . "FROM `pub` JOIN `pub_history` ON `pub_id` = `ph_pub` WHERE `ph_amend_pub`=%d ORDER BY `ph_amend_serial`",
            $pubId));
    }

    public function getLongDescriptionForPub(int $pubId)
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

    public function getCitationsForPub(int $pubId)
    {
        $query = sprintf("SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`"
            . " FROM `cite_pub` `C`"
            . " JOIN `pub` ON (`C`.`pub`=`pub_id` AND `C`.`mentions_pub`=%d)"
            . " JOIN `pub_history` ON `pub`.`pub_history`=`ph_id`", $pubId);
        return $this->fetchAll($query);
    }

    public function getTableOfContentsForPub(int $pubId, bool $fullContents)
    {
        $query = sprintf("SELECT `level`,`label`,`name` FROM `toc` WHERE `pub`=%d", $pubId);
        if (!$fullContents)
        {
            $query .= ' AND `level` < 2';
        }
        $query .= ' ORDER BY `line`';
        return $this->fetchAll($query);
    }

    public function getMirrorsForCopy(int $copyId)
    {
        $query = sprintf("SELECT REPLACE(`url`,`original_stem`,`copy_stem`) AS `mirror_url`"
                . " FROM `copy` JOIN `mirror` ON `copy`.`site`=`mirror`.`site`"
                . " WHERE `copy_id`=%d ORDER BY `rank` DESC", $copyId);
        $mirrors = array();
        foreach ($this->fetchAll($query) as $row)
        {
            array_push($mirrors, $row['mirror_url']);
        }
        return $mirrors;
    }

    public function getAmendedPub(int $pubId, int $amendSerial)
    {
        $query = sprintf("SELECT `ph_company`,`pub_id`,`ph_part`,`ph_title`,`ph_pub_date`"
                . " FROM `pub` JOIN `pub_history` ON `pub`.`pub_history`=`ph_id`"
                . " WHERE `ph_amend_pub`=%d AND `ph_amend_serial`=%d",
            $pubId, $amendSerial);
        return $this->fetch($query);
    }

    public function getCopiesForPub(int $pubId)
    {
        $query = sprintf("SELECT `format`,`copy`.`url`,`notes`,`size`,"
            . "`site`.`name`,`site`.`url` AS `site_url`,`site`.`description`,"
            . "`site`.`copy_base`,`site`.`low`,`copy`.`md5`,`copy`.`amend_serial`,"
            . "`copy`.`credits`,`copy_id`"
            . " FROM `copy`,`site`"
            . " WHERE `copy`.`site`=`site`.`site_id` AND `pub`=%d"
            . " ORDER BY `site`.`display_order`,`site`.`site_id`", $pubId);
        return $this->fetchAll($query);
    }

    public function getDetailsForPub(int $pubId)
    {
        $query = sprintf('SELECT `pub_id`, `company`.`name`, '
                . 'IFNULL(`ph_part`, "") AS `ph_part`, `ph_pub_date`, '
                . '`ph_title`, IFNULL(`ph_abstract`, "") AS `ph_abstract`, '
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
        case Company::DEC:            return ManxDatabase::sortPartNumberDEC($pn);
        case Company::TI:            return ManxDatabase::sortPartNumberTI($pn);
        case Company::TeleVideo:    return ManxDatabase::sortPartNumberTeleVideo($pn);
        case Company::Visual:        return ManxDatabase::sortPartNumberVisual($pn);
        case Company::Wyse:            return ManxDatabase::sortPartNumberWyse($pn);
        case Company::IBM:            return ManxDatabase::sortPartNumberIBM($pn);
        case Company::Motorola:        return ManxDatabase::sortPartNumberMotorola($pn);
        case Company::Interdata_PerkinElmer: return ManxDatabase::sortPartNumberInterdata($pn);
        case Company::Teletype:        return ManxDatabase::sortPartNumberTeletype($pn);
        case Company::GRI:            return ManxDatabase::sortPartNumberGRI($pn);
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

    public function searchForPublications(int $company, array $keywords, bool $online)
    {
        $matchClause = self::matchClauseForSearchWords($keywords);
        $onlineClause = $online ? "`pub_has_online_copies`" : '1=1';
        $query = "SELECT `pub_id`, `ph_part`, `ph_title`,"
                . " `pub_has_online_copies`, `ph_abstract`, `pub_has_toc`,"
                . " `pub_superseded`, `ph_pub_date`, `ph_revision`,"
                . " `ph_company`, `ph_alt_part`, `ph_pub_type`"
            . " FROM `pub`"
            . " JOIN `pub_history` ON `pub`.`pub_history` = `ph_id`"
            . " WHERE $onlineClause $matchClause"
            . " AND `ph_company`=$company"
            . " ORDER BY `ph_sort_part`, `ph_pub_date`, `pub_id`";
        return $this->fetchAll($query);
    }

    function getPublicationsSupersededByPub(int $pubId)
    {
        $query = sprintf('SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` FROM `supersession`' .
            ' JOIN `pub` ON (`old_pub`=`pub_id` AND `new_pub`=%d)' .
            ' JOIN `pub_history` ON `pub_history`=`ph_id`', $pubId);
        return $this->fetchAll($query);
    }

    function getPublicationsSupersedingPub(int $pubId)
    {
        $query = sprintf('SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` FROM `supersession`'
            . ' JOIN `pub` ON (`new_pub`=`pub_id` AND `old_pub`=%d)'
            . ' JOIN `pub_history` ON `pub_history`=`ph_id`', $pubId);
        return $this->fetchAll($query);
    }

    function getUserId(string $email, string $pw_sha1)
    {
        $rows = $this->execute("SELECT `id` FROM `user` WHERE `email`=? AND `pw_sha1`=?",
            array($email, $pw_sha1));
        return (count($rows) > 0) ? $rows[0]['id'] : -1;
    }

    function createSessionForUser(int $userId, int $sessionId, string $remoteHost, string $userAgent)
    {
        $this->execute("DELETE FROM `user_session` WHERE `user_id`=?", array($userId));
        $this->execute("INSERT INTO `user_session`"
                . "(`user_id`, `logged_in`, `ascii_session_id`, `created`, `user_agent`) "
                . "VALUES (?, 1, ?, NOW(), ?)",
            array($userId, $sessionId, $userAgent));
    }

    function deleteUserSession(string $session)
    {
        $this->execute("DELETE FROM `user_session` WHERE `ascii_session_id`=?",
            array($sessionId));
    }

    function getUserFromSessionId(int $sessionId)
    {
        $rows = $this->execute("SELECT `user_session`.`user_id`, "
                    . "`user_session`.`logged_in`, "
                    . "`user_session`.`last_impression`, "
                    . "`user`.`first_name`, `user`.`last_name` "
                . "FROM `user`, `user_session` "
                . "WHERE `ascii_session_id`=? and "
                    . "`user_session`.`user_id`=`user`.`id`",
            array($sessionId));
        if (count($rows) == 1)
        {
            $this->execute("UPDATE `user_session` "
                . "SET `last_impression` = NOW() "
                . "WHERE `ascii_session_id`=?",
                array($sessionId));
            return $rows[0];
        }

        return array();
    }

    function getPublicationsForPartNumber(string $part, int $companyId)
    {
        $part = "%" . ManxDatabase::normalizePartNumber($part) . "%";
        return $this->_db->execute("SELECT pub_id,ph_part,ph_title "
                . "FROM pub JOIN pub_history ON pub_history = ph_id "
                . "WHERE (ph_match_part LIKE ? OR ph_match_alt_part LIKE ?) AND ph_company = ? "
                . "ORDER BY ph_sort_part, ph_pub_date LIMIT 10",
            array($part, $part, $companyId));
    }

    // Add pub_history row, with ph_pub = 0
    function addPubHistory(int $userId, string $publicationType, int $companyId, string $part,
        string $altPart, string $revision, string $pubDate, string $title, string $keywords, string $notes, string $abstract,
        string $languages)
    {
        $this->_db->execute(
            'INSERT INTO `pub_history`(`ph_created`, `ph_edited_by`, `ph_pub`, '
                . '`ph_pub_type`, `ph_company`, `ph_part`, `ph_alt_part`, '
                . '`ph_revision`, `ph_pub_date`, `ph_title`, `ph_keywords`, '
                . '`ph_notes`, `ph_abstract`, `ph_lang`, '
                . '`ph_match_part`, `ph_match_alt_part`, `ph_sort_part`) '
            . 'VALUES (now(), ?, 0, '
                . '?, ?, ?, ?, '
                . '?, ?, ?, ?, '
                . '?, ?, ?, '
                . '?, ?, ?)',
            array($userId,
                $publicationType, $companyId, $part, $altPart,
                $revision, $pubDate, $title, $keywords,
                $notes, $abstract, $languages,
                ManxDatabase::normalizePartNumber($part),
                    ManxDatabase::normalizePartNumber($altPart),
                    ManxDatabase::sortPartNumber($companyId, $part)
                ));
        return $this->_db->getLastInsertId();
    }

    // Add pub row, with pub_history = ph_id
    function addPublication(int $pubHistoryId)
    {
        $this->_db->execute('INSERT INTO `pub` (`pub_history`) VALUES (?)',
            array($pubHistoryId));
        return $this->_db->getLastInsertId();
    }

    // Update pub_history row, with ph_pub = pub_id
    function updatePubHistoryPubId(int $pubHistoryId, int $pubId)
    {
        $this->_db->execute('UPDATE `pub_history` SET `ph_pub` = ? WHERE `ph_id` = ?',
            array($pubId, $pubHistoryId));
    }

    function getCompanyForId(int $companyId)
    {
        $rows = $this->_db->execute('SELECT * FROM `company` WHERE `id`=?', array($companyId));
        return (count($rows) > 0) ? $rows[0] : array();
    }

    function addCompany(string $fullName, string $shortName, string $sortName, string $display, string $notes)
    {
        $this->_db->execute('INSERT INTO `company`(`name`,`short_name`,`sort_name`,`display`,`notes`) VALUES (?,?,?,?,?)',
            array($fullName, $shortName, $sortName, $display ? 'Y' : 'N', $notes));
        return $this->_db->getLastInsertId();
    }

    function updateCompany(int $companyId, string $fullName, string $shortName, string $sortName, string $display, string $notes)
    {
        $this->_db->execute("UPDATE `company` "
                . "SET `name`=?, `short_name`=?, `sort_name`=?, `display`=?, `notes`=? "
                . "WHERE `id`=?",
            array($fullName, $shortName, $sortName, $display ? 'Y' : 'N', $notes, $id));
    }

    function getMirrors()
    {
        return $this->fetchAll("SELECT * FROM `mirror` ORDER BY `site`,`rank`");
    }

    function getSites()
    {
        return $this->fetchAll("SELECT * FROM `site` ORDER BY `display_order`");
    }

    function getFormatForExtension(string $extension)
    {
        $rows = $this->execute("SELECT `format` FROM `format_extension` WHERE `extension`=?",
            array(strtolower($extension)));
        return (count($rows) > 0) ? $rows[0]['format'] : '';
    }

    private function siteIdForName($siteName)
    {
        $siteId = $this->execute("SELECT `site_id` FROM `site` WHERE `name`=?", array($siteName));
        return $siteId[0]['site_id'];
    }

    public function getCompanyForSiteDirectory(string $siteName, string $dir)
    {
        $rows = $this->execute("SELECT `company_id` FROM `site_company_dir` WHERE `site_id`=? AND `directory`=?",
            array($this->siteIdForName($siteName), $dir));
        return (count($rows) > 0) ? $rows[0]['company_id'] : -1;
    }

    function addSupersession(int $oldPub, int $newPub)
    {
        $this->_db->execute('INSERT INTO `supersession`(`old_pub`,`new_pub`) VALUES (?,?)',
            array($oldPub, $newPub));
        $result = $this->_db->getLastInsertId();
        $this->_db->execute('UPDATE `pub` SET `pub_superseded` = 1 WHERE `pub_id` = ?',
            array($oldPub));
        return $result;
    }

    function addSite(string $name, string $url, string $description, string $copy_base, string $low, string $live)
    {
        $this->_db->execute('INSERT INTO `site`(`name`,`url`,`description`,`copy_base`,`low`,`live`) VALUES (?,?,?,?,?,?)',
            array($name, $url, $description, $copy_base, $low, $live));
        return $this->_db->getLastInsertId();
    }

    function addCopy($pubId, string $format, int $siteId, string $url,
        string $notes, $size, string $md5, string $credits, $amendSerial)
    {
        $this->beginTransaction();
        $this->_db->execute('INSERT INTO `copy`(`pub`,`format`,`site`,`url`,`notes`,`size`,`md5`,`credits`,`amend_serial`) '
            . 'VALUES (?,?,?,?,?,?,?,?,?)',
            array($pubId, $format, $siteId, $url, $notes, $size, $this->md5Value($md5), $credits, $amendSerial));
        $result = $this->_db->getLastInsertId();
        $this->_db->execute('UPDATE `pub` SET `pub_has_online_copies`=1 WHERE `pub_id`=?', array($pubId));
        $this->commit();
        return $result;
    }

    public function md5Value($md5)
    {
        return is_string($md5) ? $md5 : null;
    }

    public function addSiteDirectory(string $siteName, int $companyId, string $directory)
    {
        $siteId = $this->siteIdForName($siteName);
        $row = $this->execute("SELECT * FROM `site_company_dir` WHERE `site_id`=? AND `company_id`=?", array($siteId, $companyId));
        if (count($row) == 0)
        {
            $this->_db->execute('INSERT INTO `site_company_dir`(`site_id`,`company_id`,`directory`) VALUES (?,?,?)',
                array($siteId, $companyId, $directory));
        }
    }

    function getMostRecentDocuments(int $count)
    {
        return $this->execute(sprintf('SELECT `ph_pub`, `ph_company`, `ph_created`,'
            . ' `ph_title`, `company`.`name` AS `company_name`,'
            . ' `company`.`short_name` AS `company_short_name`,'
            . ' `ph_part`, `ph_revision`, `ph_keywords`, `ph_pub_date`,'
            . ' IFNULL(`ph_abstract`, "") AS `ph_abstract`'
            . ' FROM `pub_history`, `company`'
            . ' WHERE `pub_history`.`ph_company` = `company`.`id`'
            . ' ORDER BY `ph_created` DESC LIMIT 0,%d', $count), array());
    }

    function getManxVersion()
    {
        $row = $this->fetch(
            "SELECT `value` FROM `properties` WHERE `name`='version'");
        return (count($row) > 0) ? $row['value'] : '1';
    }

    function copyExistsForUrl(string $url)
    {
        $rows = $this->execute("SELECT `ph_company`,`ph_pub`,`ph_title` "
                . "FROM `copy`,`pub_history` "
                . "WHERE `copy`.`pub`=`pub_history`.`ph_pub` AND `copy`.`url`=?",
            array($url));
        return (count($rows) > 0) ? $rows[0] : false;
    }

    function getZeroSizeDocuments()
    {
        return $this->fetchAll("SELECT `copy_id`,`ph_company`,`ph_pub`,`ph_title` "
            . "FROM `copy`,`pub_history` "
            . "WHERE `copy`.`pub`=`pub_history`.`ph_pub` "
                . "AND (`copy`.`size` IS NULL OR `copy`.`size` = 0) "
                . "AND `copy`.`format` <> 'HTML' "
            . " LIMIT 0,10");
    }

    function getUrlForCopy(int $copyId)
    {
        $rows = $this->execute("SELECT `url` FROM `copy` WHERE `copy_id` = ?",
            array($copyId));
        return $rows[0]['url'];
    }

    function updateSizeForCopy(int $copyId, int $size)
    {
        $this->execute("UPDATE `copy` SET `size` = ? WHERE `copy_id` = ?",
            array($size, $copyId));
    }

    function updateMD5ForCopy(int $copyId, string $md5)
    {
        $this->execute("UPDATE `copy` SET `md5` = ? WHERE `copy_id` = ?",
            array($this->md5Value($md5), $copyId));
    }

    function getMissingMD5Documents()
    {
        return $this->fetchAll("SELECT `copy_id`,`ph_company`,`ph_pub`,`ph_title` "
            . "FROM `copy`,`pub_history` "
            . "WHERE `copy`.`pub`=`pub_history`.`ph_pub` "
            . "AND (`copy`.`md5` IS NULL) "
            . "AND `copy`.`format` <> 'HTML' "
            . " LIMIT 0,10");
    }

    function getProperty(string $name)
    {
        $rows = $this->execute("SELECT `value` FROM `properties` WHERE `name` = ?",
            array($name));
        return (count($rows) > 0) ? $rows[0]['value'] : false;
    }

    function setProperty(string $name, $value)
    {
        $this->execute("INSERT INTO `properties`(`name`, `value`) VALUES (?, ?) "
            . "ON DUPLICATE KEY UPDATE `value` = ?",
            array($name, $value, $value));
    }

    public function addSiteUnknownPath(string $siteName, string $path)
    {
        $this->execute(
            "INSERT INTO `site_unknown`(`site_id`,`path`) VALUES ((SELECT `site_id` FROM `site` WHERE `name`=?),?)",
            array($siteName, $path));
    }

    public function ignoreSitePath(string $siteName, string $path)
    {
        $this->beginTransaction();
        $this->execute("UPDATE `site_unknown` SET `ignored`=1 WHERE `site_id`=? AND `path`=?",
            array($this->siteIdForName($siteName), $path));
        $this->commit();
    }

    public function getSiteUnknownPathCount(string $siteName)
    {
        $siteInfo = $this->execute("SELECT `site_id`,`copy_base` FROM `site` WHERE `name`=?", array($siteName));
        $siteId = $siteInfo[0]['site_id'];
        $copyBase = $siteInfo[0]['copy_base'];
        $count = $this->execute("DELETE FROM `site_unknown` "
            . "INNER JOIN `copy` ON `url` = CONCAT(?, `path`) "
            . "WHERE `site` = ?", array($copyBase, $siteId));
        $rows = $this->execute("SELECT COUNT(*) AS `count` FROM `site_unknown` WHERE `site_id`=? AND `ignored` = 0", array($siteId));
        return $rows[0]['count'];
    }

    public function getSiteUnknownPathsOrderedById(string $siteName, int $start, bool $ascending)
    {
        $order = $ascending ? 'ASC' : 'DESC';
        return $this->execute("SELECT `path`,`id` FROM `site_unknown` WHERE `site_id`=? AND `ignored`=0 ORDER BY `id` $order LIMIT $start, 10",
        array($this->siteIdforName($siteName)));
    }

    public function getSiteUnknownPathsOrderedByPath(string $siteName, int $start, bool $ascending)
    {
        $order = $ascending ? 'ASC' : 'DESC';
        return $this->execute("SELECT `path`,`id` FROM `site_unknown` WHERE `site_id`=? AND `ignored`=0 ORDER BY `path` $order LIMIT $start, 10",
            array($this->siteIdForName($siteName)));
    }

    public function siteIgnoredPath(string $siteName, string $path)
    {
        $rows = $this->execute("SELECT COUNT(*) AS `count` FROM `site_unknown` WHERE `site_id`=? AND `path`=? AND `ignored`=1",
            array($this->siteIdForName($siteName), $path));
        return ($rows[0]['count'] > 0);
    }

    public function getAllSiteUnknownPaths(string $siteName)
    {
        return $this->execute("SELECT `id`,`path` FROM `site_unknown` WHERE `site_id`=? ORDER BY `id`",
            array($this->siteIdForName($siteName)));
    }

    public function removeSiteUnknownPathById(string $siteName, int $siteUnknownId)
    {
        return $this->execute("DELETE FROM `site_unknown` WHERE `site_id`=? AND `id`=?",
            array($this->siteIdForName($siteName), $id));
    }

    public function getPossiblyMovedSiteUnknownPaths(string $siteName)
    {
        $siteId = $this->siteIdForName($siteName);
        return $this->execute("SELECT site_unknown.path, site_unknown.id as `path_id`, copy.url, copy.copy_id, copy.md5 FROM copy, site_unknown".
                " WHERE copy.site=?" .
                " AND site_unknown.site_id=copy.site" .
                " AND REVERSE(SUBSTRING_INDEX(REVERSE(copy.url), '/', 1)) = REVERSE(SUBSTRING_INDEX(REVERSE(site_unknown.path), '/', 1));",
            array($siteId));
    }

    public function siteFileMoved(string $siteName, int $copyId, int $pathId, string $url)
    {
        $siteId = $this->siteIdForName($siteName);
        $this->execute("DELETE FROM site_unknown WHERE site_id=? AND id=?", array($siteId, $pathId));
        $this->execute("UPDATE copy SET url=? WHERE copy_id=?", array($url, $copyId));
    }
}
