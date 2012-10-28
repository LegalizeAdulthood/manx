<?php

require_once 'pages/ManxDatabase.php';
require_once 'test/FakeDatabase.php';
require_once 'test/FakeStatement.php';

class TestManxDatabase extends PHPUnit_Framework_TestCase
{
	/** @var \FakeDatabase */
	private $_db;
	/** @var \ManxDatabase */
	private $_manxDb;
	/** @var object */
	private $_statement;

	public function testConstruct()
	{
		$this->createInstance();
		$this->assertTrue(!is_null($this->_manxDb) && is_object($this->_manxDb));
	}

	public function testGetDocumentCount()
	{
		$query = "SELECT COUNT(*) FROM `pub`";
		$this->configureCountForQuery(2, $query);
		$count = $this->_manxDb->getDocumentCount();
		$this->assertCountForQuery(2, $count, $query);
	}

	public function testGetOnlineDocumentCount()
	{
		$query = "SELECT COUNT(DISTINCT `pub`) FROM `copy`";
		$this->configureCountForQuery(12, $query);
		$count = $this->_manxDb->getOnlineDocumentCount();
		$this->assertCountForQuery(12, $count, $query);
	}

	public function testGetSiteCount()
	{
		$query = "SELECT COUNT(*) FROM `site`";
		$this->configureCountForQuery(43, $query);
		$count = $this->_manxDb->getSiteCount();
		$this->assertCountForQuery(43, $count, $query);
	}

	public function testGetSiteList()
	{
		$this->createInstance();
		$query = "SELECT `url`,`description`,`low` FROM `site` WHERE `live`='Y' ORDER BY `siteid`";
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(
				array('url', 'description', 'low'),
				array(array('http://www.dec.com', 'DEC', false), array('http://www.hp.com', 'HP', true))));
		$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
		$sites = $this->_manxDb->getSiteList();
		$this->assertQueryCalledForSql($query);
		$this->assertEquals(2, count($sites));
		$this->assertColumnValuesForRows($sites, 'url', array('http://www.dec.com', 'http://www.hp.com'));
	}

	public function testGetCompanyList()
	{
		$this->createInstance();
		$query = "SELECT `id`,`name` FROM `company` WHERE `display` = 'Y' ORDER BY `sort_name`";
		$expected = array(
				array('id' => 1, 'name' => "DEC"),
				array('id' => 2, 'name' => "HP"));
		$this->configureStatementFetchAllResults($query, $expected);
		$companies = $this->_manxDb->getCompanyList();
		$this->assertQueryCalledForSql($query);
		$this->assertEquals($expected, $companies);
	}

	public function testGetDisplayLanguage()
	{
		$this->createInstance();
		$query = "SELECT IF(LOCATE(';',`eng_lang_name`),LEFT(`eng_lang_name`,LOCATE(';',`eng_lang_name`)-1),`eng_lang_name`) FROM `language` WHERE `lang_alpha_2`='fr'";
		$this->configureStatementFetchResult($query, 'French');
		$display = $this->_manxDb->getDisplayLanguage('fr');
		$this->assertQueryCalledForSql($query);
		$this->assertEquals('French', $display);
	}

	public function testGetOSTagsForPub()
	{
		$this->createInstance();
		$query = "SELECT `tag_text` FROM `tag`,`pub_tag` WHERE `tag`.`id`=`pub_tag`.`tag` AND `tag`.`class`='os' AND `pub`=5";
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(array('tag_text'),
				array(array('RSX-11M Version 4.0'), array('RSX-11M-PLUS Version 2.0'))));
		$tags = $this->_manxDb->getOSTagsForPub(5);
		$this->assertQueryCalledForSql($query);
		$this->assertEquals($tags, array('RSX-11M Version 4.0', 'RSX-11M-PLUS Version 2.0'));
	}

	public function testGetAmendmentsForPub()
	{
		$this->createInstance();
		$query = "SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`,`ph_pubdate` "
			. "FROM `pub` JOIN `pub_history` ON `pub_id` = `ph_pub` WHERE `ph_amend_pub`=3 ORDER BY `ph_amend_serial`";
		$pubId = 3;
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(
				array('ph_company', 'ph_pub', 'ph_part', 'ph_title', 'ph_pubdate'),
				array(array(1, 4496, 'DEC-15-YWZA-DN1', 'DDT (Dynamic Debugging Technique) Utility Program', '1970-04'),
					array(1, 3301, 'DEC-15-YWZA-DN3', 'SGEN System Generator Utility Program', '1970-09'))));
		$amendments = $this->_manxDb->getAmendmentsForPub($pubId);
		$this->assertQueryCalledForSql($query);
		$this->assertArrayHasLength($amendments, 2);
		$this->assertColumnValuesForRows($amendments, 'ph_pub', array(4496, 3301));
	}

	public function testGetLongDescriptionForPubDoesNothing()
	{
		$this->createInstance();
		$pubId = 3;
		$query = "SELECT 'html_text' FROM `long_desc` WHERE `pub`=3 ORDER BY `line`";
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(array('html_text'),
				array(array('<p>This is paragraph one.</p>'), array('<p>This is paragraph two.</p>'))));
		$longDescription = $this->_manxDb->getLongDescriptionForPub($pubId);
		$this->assertFalse($this->_db->queryCalled);
	}

	public function testGetCitationsForPub()
	{
		$this->createInstance();
		$pubId = 72;
		$query = 'SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` '
			. 'FROM `cite_pub` `C`'
			. ' JOIN `pub` ON (`C`.`pub`=`pub_id` AND `C`.`mentions_pub`=72)'
			. ' JOIN `pub_history` ON `pub`.`pub_history`=`ph_id`';
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(
				array('ph_company', 'ph_pub', 'ph_part', 'ph_title'),
				array(array(1, 123, 'EK-306AA-MG-001', 'KA655 CPU System Maintenance'))));
		$citations = $this->_manxDb->getCitationsForPub($pubId);
		$this->assertQueryCalledForSql($query);
		$this->assertArrayHasLength($citations, 1);
		$this->assertEquals('EK-306AA-MG-001', $citations[0]['ph_part']);
	}

	public function testGetTableOfContentsForPubFullContents()
	{
		$this->createInstance();
		$pubId = 123;
		$query = "SELECT `level`,`label`,`name` FROM `toc` WHERE `pub`=123 ORDER BY `line`";
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(
				array('level', 'label', 'name'),
				array(
					array(1, 'Chapter 2', 'Configuration'),
					array(2, '2.4', 'DSSI Configuration'),
					array(3, '2.4.4', 'DSSI Cabling'),
					array(4, '2.4.4.1', 'DSSI Bus Termination and Length'),
					array(1, 'Appendix C', 'Related Documentation'))));
		$toc = $this->_manxDb->getTableOfContentsForPub($pubId, true);
		$this->assertQueryCalledForSql($query);
		$this->assertArrayHasLength($toc, 5);
		$this->assertColumnValuesForRows($toc, 'label',
			array('Chapter 2', '2.4', '2.4.4', '2.4.4.1', 'Appendix C'));
	}

	public function testGetTableOfContentsForPubAbbreviatedContents()
	{
		$this->createInstance();
		$pubId = 123;
		$query = "SELECT `level`,`label`,`name` FROM `toc` WHERE `pub`=123 AND `level` < 2 ORDER BY `line`";
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(
				array('level', 'label', 'name'),
				array(
					array(1, 'Chapter 1', 'KA655 CPU and Memory Subsystem'),
					array(1, 'Chapter 2', 'Configuration'),
					array(1, 'Chapter 3', 'KA655 Firmware'),
					array(1, 'Chapter 4', 'Troubleshooting and Diagnostics'),
					array(1, 'Appendix A', 'Configuring the KFQSA'),
					array(1, 'Appendix B', 'KA655 CPU Address Assignments'),
					array(1, 'Appendix C', 'Related Documentation'))
			));
		$toc = $this->_manxDb->getTableOfContentsForPub($pubId, false);
		$this->assertQueryCalledForSql($query);
		$this->assertArrayHasLength($toc, 7);
		$this->assertColumnValuesForRows($toc, 'label',
			array('Chapter 1', 'Chapter 2', 'Chapter 3', 'Chapter 4', 'Appendix A', 'Appendix B', 'Appendix C'));
	}

	public function testGetMirrorsForCopy()
	{
		$this->createInstance();
		$copyId = 7165;
		$query = "SELECT REPLACE(`url`,`original_stem`,`copy_stem`) AS `mirror_url`"
				. " FROM `copy` JOIN `mirror` ON `copy`.`site`=`mirror`.`site`"
				. " WHERE `copyid`=7165 ORDER BY `rank` DESC";
		$expected = array('http://bitsavers.trailing-edge.com/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
			'http://www.bighole.nl/pub/mirror/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
			'http://www.textfiles.com/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
			'http://computer-refuge.org/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
			'http://www.mirrorservice.org/sites/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf');
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(array('mirror_url'),
				array(array($expected[0]), array($expected[1]), array($expected[2]), array($expected[3]), array($expected[4]))));
		$mirrors = $this->_manxDb->getMirrorsForCopy($copyId);
		$this->assertQueryCalledForSql($query);
		$this->assertEquals($expected, $mirrors);
	}

	public function testGetAmendedPub()
	{
		$this->createInstance();
		$pubId = 17970;
		$amendSerial = 7;
		$query = sprintf("SELECT `ph_company`,`pub_id`,`ph_part`,`ph_title`,`ph_pubdate`"
					. " FROM `pub` JOIN `pub_history` ON `pub`.`pub_history`=`ph_id`"
					. " WHERE `ph_amend_pub`=%d AND `ph_amend_serial`=%d", $pubId, $amendSerial);
		$expected = array('ph_company' => 7, 'pub_id' => 57, 'ph_part' => 'AB81-14G',
				'ph_title' => 'Honeywell Publications Catalog Addendum G', 'ph_pubdate' => '1984-02');
		$this->configureStatementFetchResult($query, $expected);
		$amended = $this->_manxDb->getAmendedPub($pubId, $amendSerial);
		$this->assertQueryCalledForSql($query);
		$this->assertEquals($expected, $amended);
	}

	public function testGetCopiesForPub()
	{
		$this->createInstance();
		$pubId = 123;
		$query = "SELECT `format`,`copy`.`url`,`notes`,`size`,"
			. "`site`.`name`,`site`.`url` AS `site_url`,`site`.`description`,"
			. "`site`.`copy_base`,`site`.`low`,`copy`.`md5`,`copy`.`amend_serial`,"
			. "`copy`.`credits`,`copyid`"
			. " FROM `copy`,`site`"
			. " WHERE `copy`.`site`=`site`.`siteid` AND `pub`=123"
			. " ORDER BY `site`.`display_order`,`site`.`siteid`";
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(
			array('format', 'url', 'notes', 'size', 'name', 'site_url', 'description', 'copy_base', 'low', 'md5', 'amend_serial', 'credits', 'copyid'),
			array(
				array('PDF', 'http://bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf', NULL, 25939827, 'bitsavers', 'http://bitsavers.org/', "Al Kossow's Bitsavers", 'http://bitsavers.org/pdf/', 'N', '0f91ba7f8d99ce7a9b57f9fdb07d3561', 7, NULL, 10277)
				)));
		$copies = $this->_manxDb->getCopiesForPub($pubId);
		$this->assertQueryCalledForSql($query);
		$this->assertArrayHasLength($copies, 1);
		$this->assertEquals('http://bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf', $copies[0]['url']);
	}

	public function testGetDetailsForPub()
	{
		$this->createInstance();
		$pubId = 3;
		$query = 'SELECT `pub_id`, `company`.`name`, '
			. 'IFNULL(`ph_part`, "") AS `ph_part`, `ph_pubdate`, '
			. '`ph_title`, IFNULL(`ph_abstract`, "") AS `ph_abstract`, '
			. 'IFNULL(`ph_revision`, "") AS `ph_revision`, `ph_ocr_file`, '
			. '`ph_cover_image`, `ph_lang`, `ph_keywords` '
			. 'FROM `pub` '
			. 'JOIN `pub_history` ON `pub`.`pub_history`=`ph_id` '
			. 'JOIN `company` ON `ph_company`=`company`.`id` '
			. 'WHERE 1=1 AND `pub_id`=3';
		$rows = FakeDatabase::createResultRowsForColumns(
			array('pub_id', 'name', 'ph_part', 'ph_pubdate', 'ph_title', 'ph_abstract', 'ph_revision', 'ph_ocr_file', 'ph_cover_image', 'ph_lang', 'ph_keywords'),
			array(array(3, 'Digital Equipment Corporation', 'AA-K336A-TK', NULL, 'GIGI/ReGIS Handbook', NULL, '', NULL, 'gigi_regis_handbook.png', '+en', 'VK100')));
		$this->configureStatementFetchResult($query, $rows[0]);
		$details = $this->_manxDb->getDetailsForPub($pubId);
		$this->assertQueryCalledForSql($query);
		$this->assertEquals($rows[0], $details);
	}

	public function testNormalizePartNumberNotString()
	{
		$this->assertEquals('', ManxDatabase::normalizePartNumber(array()));
	}

	public function testNormalizePartNumberLowerCase()
	{
		$this->assertEquals('UC', ManxDatabase::normalizePartNumber('uc'));
	}

	public function testNormalizePartNumberNonAlphaNumeric()
	{
		$this->assertEquals('UC122', ManxDatabase::normalizePartNumber(' !u,c,1,2,2 ,./<>?;' . "'" . ':"[]{}\\|`~!@#$%^&*()'));
	}

	public function testNormalizePartNumberLetterOhIsZero()
	{
		$this->assertEquals('UC1220', ManxDatabase::normalizePartNumber(' !u,c,1,2,2,o ,./<>?;' . "'" . ':"[]{}\\|`~!@#$%^&*()'));
	}

	public function testCleanSqlWordNotString()
	{
		$this->assertEquals('', ManxDatabase::cleanSqlWord(array()));
	}

	public function testCleanSqlWordNoSpecials()
	{
		$this->assertEquals('cleanWord', ManxDatabase::cleanSqlWord('cleanWord'));
	}

	public function testCleanSqlWordPercent()
	{
		$this->assertEquals('percent\\%Word', ManxDatabase::cleanSqlWord('percent%Word'));
	}

	public function testCleanSqlWordQuote()
	{
		$this->assertEquals("quote\\'Word", ManxDatabase::cleanSqlWord("quote'Word"));
	}

	public function testCleanSqlWordUnderline()
	{
		$this->assertEquals('underline\\_Word', ManxDatabase::cleanSqlWord('underline_Word'));
	}

	public function testCleanSqlWordBackslash()
	{
		$this->assertEquals('backslash\\\\Word', ManxDatabase::cleanSqlWord('backslash\\Word'));
	}

	public function testMatchClauseForSearchWordsSingleKeyword()
	{
		$clause = ManxDatabase::matchClauseForSearchWords(array('terminal'));
		$this->assertEquals(" AND ((`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
			. "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
	}

	public function testMatchClauseForMultipleKeywords()
	{
		$clause = ManxDatabase::matchClauseForSearchWords(array('graphics', 'terminal'));
		$this->assertEquals(" AND ((`ph_title` LIKE '%graphics%' OR `ph_keywords` LIKE '%graphics%' "
			. "OR `ph_match_part` LIKE '%GRAPHICS%' OR `ph_match_alt_part` LIKE '%GRAPHICS%') "
			. "AND (`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
			. "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
	}

	public function testSearchForPublications()
	{
		$this->createInstance();
		$rows = array(
			array('pub_id' => 1, 'ph_part' => '', 'ph_title' => '', 'pub_has_online_copies' => '',
				'ph_abstract' => '', 'pub_has_toc' => '', 'pub_superseded' => '',
				'ph_pubdate' => '', 'ph_revision' => '', 'ph_company' => '', 'ph_alt_part' => '',
				'ph_pubtype' => '')
			);
		$keywords = array('graphics', 'terminal');
		$matchClause = ManxDatabase::matchClauseForSearchWords($keywords);
		$company = 1;
		$query = "SELECT `pub_id`, `ph_part`, `ph_title`,"
			. " `pub_has_online_copies`, `ph_abstract`, `pub_has_toc`,"
			. " `pub_superseded`, `ph_pubdate`, `ph_revision`,"
			. " `ph_company`, `ph_alt_part`, `ph_pubtype` FROM `pub`"
			. " JOIN `pub_history` ON `pub`.`pub_history` = `ph_id`"
			. " WHERE `pub_has_online_copies` $matchClause"
			. " AND `ph_company`=$company"
			. " ORDER BY `ph_sort_part`, `ph_pubdate`, `pub_id`";
		$this->configureStatementFetchAllResults($query, $rows);
		$pubs = $this->_manxDb->searchForPublications($company, $keywords, true);
		$this->assertQueryCalledForSql($query);
		$this->assertEquals($rows, $pubs);
	}

	public function testGetPublicationsSupersededByPub()
	{
		$this->createInstance();
		$pubId = 6105;
		$query = sprintf('SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` FROM `supersession`' .
			' JOIN `pub` ON (`old_pub`=`pub_id` AND `new_pub`=%d)' .
			' JOIN `pub_history` ON `pub_history`=`ph_id`', $pubId);
		$rows = array(array('ph_company' => 1, 'ph_pub' => 23, 'ph_part' => 'EK-11024-TM-PRE', 'ph_title' => 'PDP-11/24 System Technical Manual'));
		$this->configureStatementFetchAllResults($query, $rows);
		$pubs = $this->_manxDb->getPublicationsSupersededByPub($pubId);
		$this->assertQueryCalledForSql($query);
		$this->assertEquals($rows, $pubs);
	}

	public function testGetPublicationsSupersedingPub()
	{
		$this->createInstance();
		$pubId = 23;
		$query = sprintf('SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` FROM `supersession`'
			. ' JOIN `pub` ON (`new_pub`=`pub_id` AND `old_pub`=%d)'
			. ' JOIN `pub_history` ON `pub_history`=`ph_id`', $pubId);
		$rows = array(array('ph_company' => 1, 'ph_pub' => 6105, 'ph_part' => 'EK-11024-TM-001', 'ph_title' => 'PDP-11/24 System Technical Manual'));
		$this->configureStatementFetchAllResults($query, $rows);
		$pubs = $this->_manxDb->getPublicationsSupersedingPub($pubId);
		$this->assertQueryCalledForSql($query);
		$this->assertEquals($rows, $pubs);
	}

	public function testAddCopy()
	{
		$this->createInstance();
		$query = 'INSERT INTO `copy`'
			. '(`pub`,`format`,`site`,`url`,`notes`,`size`,`md5`,`credits`,`amend_serial`) '
			. 'VALUES (?,?,?,?,?,?,?,?,?)';
		$pubId = 23;
		$format = 'PDF';
		$siteId = 5;
		$url = 'http://foo.bar';
		$notes = '';
		$size = '';
		$md5 = '';
		$credits = '';
		$amendSerial = '';
		$this->_manxDb->addCopy($pubId, $format, $siteId, $url,
				$notes, $size, $md5, $credits, $amendSerial);
		$this->assertTrue($this->_db->executeCalled);
		$this->assertEquals(2, count($this->_db->executeLastStatements));
		$this->assertEquals($query, $this->_db->executeLastStatements[0]);
		$this->assertTrue($this->_db->getLastInsertIdCalled);
	}

	public function testGetMostRecentDocuments()
	{
		$this->createInstance();
		$count = 200;
		$query = sprintf('SELECT `ph_pub`, `ph_company`, `ph_created`, `ph_title`, '
			. '`company`.`name` AS `company_name`, `ph_part`, `ph_revision`, `ph_keywords`, `ph_pubdate`, '
			. 'IFNULL(`ph_abstract`, "") AS `ph_abstract` '
			. 'FROM `pub_history`, `company` '
			. 'WHERE `pub_history`.`ph_company` = `company`.`id` '
			. 'ORDER BY `ph_created` DESC LIMIT 0,%d', $count);
		$rows = $this->_manxDb->getMostRecentDocuments(200);
		$this->assertTrue($this->_db->executeCalled);
		$this->assertEquals($query, $this->_db->executeLastStatements[0]);
	}

	public function testGetManxVersion()
	{
		$this->createInstance();
		$query = "SELECT `value` FROM `properties` WHERE `name`='version'";
		$this->configureStatementFetchResult($query, '2');
		$version = $this->_manxDb->getManxVersion();
		$this->assertTrue($this->_statement->fetchCalled);
		$this->assertEquals('2', $version);
	}

	public function testSortPartNumberGRINoMatch()
	{
		$this->assertEquals('XX', ManxDatabase::sortPartNumberGRI('XX'));
	}

	public function testSortPartNumberGRIMatch()
	{
		$this->assertEquals('12034XYZ', ManxDatabase::sortPartNumberGRI('12-34-XYZ'));
	}

	public function testSortPartNumberTeletypeNoMatch()
	{
		$this->assertEquals('XYZ1234', ManxDatabase::sortPartNumberTeletype('XYZ-1234'));
	}

	public function testSortPartNumberTeletypeMatch()
	{
		$this->assertEquals('0123XYZ', ManxDatabase::sortPartNumberTeletype('123-XYZ'));
	}

	public function testSortPartNumberInterdataNoMatch()
	{
		$this->assertEquals('123XYZ', ManxDatabase::sortPartNumberInterdata('123-XYZ'));
	}

	public function testSortPartNumberInterdataMatch()
	{
		$this->assertEquals('123XYZ', ManxDatabase::sortPartNumberInterdata('ABC-123-XYZ'));
	}

	public function testSortPartNumberMotorolaNoMatch()
	{
		$this->assertEquals('123-XYZ', ManxDatabase::sortPartNumberMotorola('123-XYZ'));
	}

	public function testSortPartNumberMotorolaMatch()
	{
		$this->assertEquals('AN01234-XYZ', ManxDatabase::sortPartNumberMotorola('AN1234-XYZ'));
	}

	public function testSortPartNumberIBMNoMatch()
	{
		$this->assertEquals('WXYZ123', ManxDatabase::sortPartNumberIBM('WXYZ-123'));
	}

	public function testSortPartNumberIBMMatch()
	{
		$this->assertEquals('A12345600', ManxDatabase::sortPartNumberIBM('12-3456'));
	}

	public function testSortPartNumberWyseNoMatch()
	{
		$this->assertEquals('WY123', ManxDatabase::sortPartNumberWyse('WY-123'));
	}

	public function testSortPartNumberWyseMatch()
	{
		$this->assertEquals('12034567', ManxDatabase::sortPartNumberWyse('12-345-67'));
	}

	public function testSortPartNumberVisualNoMatch()
	{
		$this->assertEquals('1121', ManxDatabase::sortPartNumberVisual('1121'));
	}

	public function testSortPartNumberVisualMatch()
	{
		$this->assertEquals('123', ManxDatabase::sortPartNumberVisual('AB-123-XY'));
	}

	public function testSortPartNumberTeleVideoNoMatch()
	{
		$this->assertEquals('12345678', ManxDatabase::sortPartNumberTeleVideo('1234-5678'));
	}

	public function testSortPartNumberTeleVideoMatch()
	{
		$this->assertEquals('0300013001', ManxDatabase::sortPartNumberTeleVideo('B300013-001'));
	}

	public function testSortPartNumberTIMatch()
	{
		$this->assertEquals('01234561234', ManxDatabase::sortPartNumberTI('123456-1234'));
	}

	public function testSortPartNumberTINoMatch()
	{
		$this->assertEquals('XYZ123', ManxDatabase::sortPartNumberTI('XYZ-123'));
	}

	public function testSortPartNumberDECMatch()
	{
		$this->assertEquals('FOO000', ManxDatabase::sortPartNumberDEC('FOO-PRE9969'));
	}

	public function testSortPartNumberDECMatchRT11()
	{
		$this->assertEquals('ADC740009', ManxDatabase::sortPartNumberDEC('ADC7400B9'));
	}

	public function testSortPartNumberDECNoMatch()
	{
		$this->assertEquals('XYZ123', ManxDatabase::sortPartNumberDEC('XYZ-123'));
	}

	public function testCopyExistsForUrlReturnsTrueWhenDatabaseContainsUrl()
	{
		$this->createInstance();
		$this->_db->executeFakeResult = FakeDatabase::createResultRowsForColumns(
			array('ph_company', 'ph_pub', 'ph_title'),
			array(array('1', '2', 'IM1 Schematic')));
		$row = $this->_manxDb->copyExistsForUrl('http://bitsavers.org/pdf/sgi/iris/IM1_Schematic.pdf');
		$this->assertTrue($this->_db->executeCalled);
		$this->assertEquals(3, count($row));
		$this->assertEquals('1', $row['ph_company']);
		$this->assertEquals('2', $row['ph_pub']);
		$this->assertEquals('IM1 Schematic', $row['ph_title']);
	}

	public function testCopyExistsForUrlReturnsFalseWhenDatabaseOmitsUrl()
	{
		$this->createInstance();
		$this->_db->executeFakeResult = array();
		$this->assertFalse($this->_manxDb->copyExistsForUrl('http://bitsavers.org/pdf/sgi/iris/IM1_Schematic.pdf'));
	}

	public function testGetZeroSizeDocuments()
	{
		$this->createInstance();
		$query = "SELECT `copyid`,`ph_company`,`ph_pub`,`ph_title` "
			. "FROM `copy`,`pub_history` "
			. "WHERE `copy`.`pub`=`pub_history`.`ph_pub` "
			. "AND (`copy`.`size` IS NULL OR `copy`.`size` = 0) "
			. "AND `copy`.`format` <> 'HTML' "
			. " LIMIT 0,10";
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(
				array('copyid', 'ph_company', 'ph_pub', 'ph_title'),
				array(array('66', '1', '2', 'IM1 Schematic'))));
		$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
		$rows = $this->_manxDb->getZeroSizeDocuments();
		$this->assertQueryCalledForSql($query);
		$this->assertEquals(1, count($rows));
		$this->assertEquals(66, $rows[0]['copyid']);
		$this->assertEquals(1, $rows[0]['ph_company']);
		$this->assertEquals(2, $rows[0]['ph_pub']);
		$this->assertEquals('IM1 Schematic', $rows[0]['ph_title']);
	}

	public function testGetUrlForCopy()
	{
		$this->createInstance();
		$query = "SELECT `url` FROM `copy` WHERE `copyid` = ?";
		$url = 'http://www.example.com/foo.pdf';
		$this->_db->executeFakeResult = FakeDatabase::createResultRowsForColumns(
			array('url'),
			array(array($url)));
		$actualUrl = $this->_manxDb->getUrlForCopy(5);
		$this->assertTrue($this->_db->executeCalled);
		$this->assertEquals(1, count($this->_db->executeLastStatements));
		$this->assertEquals($query, $this->_db->executeLastStatements[0]);
		$this->assertEquals($url, $actualUrl);
	}

	public function testUpdateSizeForCopy()
	{
		$this->createInstance();
		$query = "UPDATE `copy` SET `size` = ? WHERE `copyid` = ?";
		$copyId = 5;
		$size = 4096;
		$this->_manxDb->updateSizeForCopy($copyId, $size);
		$this->assertTrue($this->_db->executeCalled);
		$this->assertEquals(1, count($this->_db->executeLastStatements));
		$this->assertEquals($query, $this->_db->executeLastStatements[0]);
		$this->assertEquals($size, $this->_db->executeLastArgs[0][0]);
		$this->assertEquals($copyId, $this->_db->executeLastArgs[0][1]);
	}

	public function testUpdateMD5ForCopy()
	{
		$this->createInstance();
		$query = "UPDATE `copy` SET `md5` = ? WHERE `copyid` = ?";
		$copyId = 5;
		$md5 = 'e7e98fb955892f73507d7b3a1874f9ee';
		$this->_manxDb->updateMD5ForCopy($copyId, $md5);
		$this->assertTrue($this->_db->executeCalled);
		$this->assertEquals(1, count($this->_db->executeLastStatements));
		$this->assertEquals($query, $this->_db->executeLastStatements[0]);
		$this->assertEquals($md5, $this->_db->executeLastArgs[0][0]);
		$this->assertEquals($copyId, $this->_db->executeLastArgs[0][1]);
	}

	public function testGetMissingMD5Documents()
	{
		$this->createInstance();
		$query = "SELECT `copyid`,`ph_company`,`ph_pub`,`ph_title` "
			. "FROM `copy`,`pub_history` "
			. "WHERE `copy`.`pub`=`pub_history`.`ph_pub` "
			. "AND (`copy`.`md5` IS NULL) "
			. "AND `copy`.`format` <> 'HTML' "
			. " LIMIT 0,10";
		$this->configureStatementFetchAllResults($query,
			FakeDatabase::createResultRowsForColumns(
				array('copyid', 'ph_company', 'ph_pub', 'ph_title'),
				array(array('66', '1', '2', 'IM1 Schematic'))));
		$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
		$rows = $this->_manxDb->getMissingMD5Documents();
		$this->assertQueryCalledForSql($query);
		$this->assertEquals(1, count($rows));
		$this->assertEquals(66, $rows[0]['copyid']);
		$this->assertEquals(1, $rows[0]['ph_company']);
		$this->assertEquals(2, $rows[0]['ph_pub']);
		$this->assertEquals('IM1 Schematic', $rows[0]['ph_title']);
	}

	private function assertColumnValuesForRows($rows, $column, $values)
	{
		$this->assertEquals(count($rows), count($values), "different number of expected values from the number of rows");
		$i = 0;
		foreach ($rows as $row)
		{
			$this->assertTrue(array_key_exists($column, $row), sprintf("row doesn't contain key '%s'", $column));
			$this->assertEquals($row[$column], $values[$i], "expected value doesn't match value in column");
			++$i;
		}
	}

	private function assertArrayHasLength($value, $length)
	{
		$this->assertTrue(is_array($value));
		$this->assertEquals($length, count($value));
	}

	private function createInstance()
	{
		$this->_db = new FakeDatabase();
		$this->_manxDb = ManxDatabase::getInstanceForDatabase($this->_db);
		$this->_statement = new FakeStatement();
	}

	private function configureCountForQuery($expectedCount, $query)
	{
		$this->createInstance();
		$this->configureStatementFetchResult($query, array($expectedCount));
	}

	private function assertCountForQuery($expectedCount, $count, $query)
	{
		$this->assertQueryCalledForSql($query);
		$this->assertTrue($this->_statement->fetchCalled);
		$this->assertEquals($expectedCount, $count);
	}

	private function assertQueryCalledForSql($sql)
	{
		$this->assertTrue($this->_db->queryCalled);
		$this->assertEquals($sql, $this->_db->queryLastStatement);
	}

	private function configureStatementFetchResult($query, $result)
	{
		$this->_statement->fetchFakeResult = $result;
		$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
	}

	private function configureStatementFetchAllResults($query, $results)
	{
		$this->_statement->fetchAllFakeResult = $results;
		$this->_db->queryFakeResultsForQuery[$query] = $this->_statement;
	}
}

?>
