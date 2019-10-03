<?php

require_once 'pages/ManxDatabase.php';
require_once 'test/DatabaseTester.php';

class TestManxDatabase extends PHPUnit\Framework\TestCase
{
    /** @var IDatabase */
    private $_db;
    /** @var ManxDatabase */
    private $_manxDb;
    /** @var PDOStatement */
    private $_statement;

    protected function setUp()
    {
        $this->_statement = $this->createMock(PDOStatement::class);
        $this->_db = $this->createMock(IDatabase::class);
        $this->_manxDb = ManxDatabase::getInstanceForDatabase($this->_db);
    }

    public function testConstruct()
    {
        $this->assertTrue(!is_null($this->_manxDb) && is_object($this->_manxDb));
    }

    public function testGetDocumentCount()
    {
        $query = "SELECT COUNT(*) FROM `pub`";
        $this->_statement->expects($this->once())->method('fetch')->willReturn(array(2));
        $this->_db->expects($this->once())->method('query')
            ->with($query)
            ->willReturn($this->_statement);

        $count = $this->_manxDb->getDocumentCount();

        $this->assertEquals(2, $count);
    }

    public function testGetOnlineDocumentCount()
    {
        $query = "SELECT COUNT(DISTINCT `pub`) FROM `copy`";
        $this->_statement->expects($this->once())->method('fetch')->willReturn(array(12));
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $count = $this->_manxDb->getOnlineDocumentCount();

        $this->assertEquals(12, $count);
    }

    public function testGetSiteCount()
    {
        $query = "SELECT COUNT(*) FROM `site`";
        $this->_statement->expects($this->once())->method('fetch')->willReturn(array(43));
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $count = $this->_manxDb->getSiteCount();

        $this->assertEquals(43, $count);
    }

    public function testGetSiteList()
    {
        $query = "SELECT `url`,`description`,`low` FROM `site` WHERE `live`='Y' ORDER BY `site_id`";
        $this->_statement->expects($this->once())->method('fetchAll')
            ->willReturn(DatabaseTester::createResultRowsForColumns(
                array('url', 'description', 'low'),
                array(array('http://www.dec.com', 'DEC', false), array('http://www.hp.com', 'HP', true))));
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $sites = $this->_manxDb->getSiteList();

        $this->assertEquals(2, count($sites));
        $this->assertColumnValuesForRows($sites, 'url', array('http://www.dec.com', 'http://www.hp.com'));
    }

    public function testGetCompanyList()
    {
        $query = "SELECT `id`,`name` FROM `company` WHERE `display` = 'Y' ORDER BY `sort_name`";
        $expected = array(
                array('id' => 1, 'name' => "DEC"),
                array('id' => 2, 'name' => "HP"));
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn($expected);
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $companies = $this->_manxDb->getCompanyList();

        $this->assertEquals($expected, $companies);
    }

    public function testGetDisplayLanguage()
    {
        $this->_statement->expects($this->once())->method('fetch')->willReturn('French');
        $query = "SELECT IF(LOCATE(';',`eng_lang_name`),LEFT(`eng_lang_name`,LOCATE(';',`eng_lang_name`)-1),`eng_lang_name`) FROM `language` WHERE `lang_alpha_2`='fr'";
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $display = $this->_manxDb->getDisplayLanguage('fr');

        $this->assertEquals('French', $display);
    }

    public function testGetOSTagsForPub()
    {
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn(
            DatabaseTester::createResultRowsForColumns(array('tag_text'),
                array(array('RSX-11M Version 4.0'), array('RSX-11M-PLUS Version 2.0')))
        );
        $query = "SELECT `tag_text` FROM `tag`,`pub_tag` WHERE `tag`.`id`=`pub_tag`.`tag` AND `tag`.`class`='os' AND `pub`=5";
        $this->_db->expects($this->once())->method('query')
            ->with($query)
            ->willReturn($this->_statement);

        $tags = $this->_manxDb->getOSTagsForPub(5);

        $this->assertEquals($tags, array('RSX-11M Version 4.0', 'RSX-11M-PLUS Version 2.0'));
    }

    public function testGetAmendmentsForPub()
    {
        $query = "SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title`,`ph_pub_date` "
            . "FROM `pub` JOIN `pub_history` ON `pub_id` = `ph_pub` WHERE `ph_amend_pub`=3 ORDER BY `ph_amend_serial`";
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn(
            DatabaseTester::createResultRowsForColumns(
                array('ph_company', 'ph_pub', 'ph_part', 'ph_title', 'ph_pub_date'),
                array(array(1, 4496, 'DEC-15-YWZA-DN1', 'DDT (Dynamic Debugging Technique) Utility Program', '1970-04'),
                    array(1, 3301, 'DEC-15-YWZA-DN3', 'SGEN System Generator Utility Program', '1970-09'))));
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);
        $pubId = 3;

        $amendments = $this->_manxDb->getAmendmentsForPub($pubId);

        $this->assertArrayHasLength($amendments, 2);
        $this->assertColumnValuesForRows($amendments, 'ph_pub', array(4496, 3301));
    }

    public function testGetLongDescriptionForPubDoesNothing()
    {
        $pubId = 3;
        // Uncomment this code when the method really does a search.
        // $query = "SELECT 'html_text' FROM `long_desc` WHERE `pub`=3 ORDER BY `line`";
        // $this->expectStatementFetchAllResults($query, array()
        //     DatabaseTester::createResultRowsForColumns(array('html_text'),
        //         array(array('<p>This is paragraph one.</p>'), array('<p>This is paragraph two.</p>'))));
        $this->_db->expects($this->never())->method('query');

        $longDescription = $this->_manxDb->getLongDescriptionForPub($pubId);

        $this->assertEquals(array(), $longDescription);
    }

    public function testGetCitationsForPub()
    {
        $pubId = 72;
        $query = 'SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` '
            . 'FROM `cite_pub` `C`'
            . ' JOIN `pub` ON (`C`.`pub`=`pub_id` AND `C`.`mentions_pub`=72)'
            . ' JOIN `pub_history` ON `pub`.`pub_history`=`ph_id`';
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn(
            DatabaseTester::createResultRowsForColumns(
                array('ph_company', 'ph_pub', 'ph_part', 'ph_title'),
                array(array(1, 123, 'EK-306AA-MG-001', 'KA655 CPU System Maintenance'))));
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $citations = $this->_manxDb->getCitationsForPub($pubId);

        $this->assertArrayHasLength($citations, 1);
        $this->assertEquals('EK-306AA-MG-001', $citations[0]['ph_part']);
    }

    public function testGetTableOfContentsForPubFullContents()
    {
        $pubId = 123;
        $query = "SELECT `level`,`label`,`name` FROM `toc` WHERE `pub`=123 ORDER BY `line`";
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn(
            DatabaseTester::createResultRowsForColumns(
                array('level', 'label', 'name'),
                array(
                    array(1, 'Chapter 2', 'Configuration'),
                    array(2, '2.4', 'DSSI Configuration'),
                    array(3, '2.4.4', 'DSSI Cabling'),
                    array(4, '2.4.4.1', 'DSSI Bus Termination and Length'),
                    array(1, 'Appendix C', 'Related Documentation'))));
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $toc = $this->_manxDb->getTableOfContentsForPub($pubId, true);

        $this->assertArrayHasLength($toc, 5);
        $this->assertColumnValuesForRows($toc, 'label',
            array('Chapter 2', '2.4', '2.4.4', '2.4.4.1', 'Appendix C'));
    }

    public function testGetTableOfContentsForPubAbbreviatedContents()
    {
        $pubId = 123;
        $query = "SELECT `level`,`label`,`name` FROM `toc` WHERE `pub`=123 AND `level` < 2 ORDER BY `line`";
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn(
            DatabaseTester::createResultRowsForColumns(
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
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $toc = $this->_manxDb->getTableOfContentsForPub($pubId, false);

        $this->assertArrayHasLength($toc, 7);
        $this->assertColumnValuesForRows($toc, 'label',
            array('Chapter 1', 'Chapter 2', 'Chapter 3', 'Chapter 4', 'Appendix A', 'Appendix B', 'Appendix C'));
    }

    public function testGetMirrorsForCopy()
    {
        $copyId = 7165;
        $query = "SELECT REPLACE(`url`,`original_stem`,`copy_stem`) AS `mirror_url`"
                . " FROM `copy` JOIN `mirror` ON `copy`.`site`=`mirror`.`site`"
                . " WHERE `copy_id`=7165 ORDER BY `rank` DESC";
        $expected = array('http://bitsavers.trailing-edge.com/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
            'http://www.bighole.nl/pub/mirror/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
            'http://www.textfiles.com/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
            'http://computer-refuge.org/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
            'http://www.mirrorservice.org/sites/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf');
        $rows = DatabaseTester::createResultRowsForColumns(array('mirror_url'),
                array(array($expected[0]), array($expected[1]), array($expected[2]), array($expected[3]), array($expected[4])));
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn($rows);
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $mirrors = $this->_manxDb->getMirrorsForCopy($copyId);

        $this->assertEquals($expected, $mirrors);
    }

    public function testGetAmendedPub()
    {
        $pubId = 17970;
        $amendSerial = 7;
        $query = sprintf("SELECT `ph_company`,`pub_id`,`ph_part`,`ph_title`,`ph_pub_date`"
                    . " FROM `pub` JOIN `pub_history` ON `pub`.`pub_history`=`ph_id`"
                    . " WHERE `ph_amend_pub`=%d AND `ph_amend_serial`=%d", $pubId, $amendSerial);
        $expected = array('ph_company' => 7, 'pub_id' => 57, 'ph_part' => 'AB81-14G',
                'ph_title' => 'Honeywell Publications Catalog Addendum G', 'ph_pub_date' => '1984-02');
        $this->_statement->expects($this->once())->method('fetch')->willReturn($expected);
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $amended = $this->_manxDb->getAmendedPub($pubId, $amendSerial);

        $this->assertEquals($expected, $amended);
    }

    public function testGetCopiesForPub()
    {
        $pubId = 123;
        $query = "SELECT `format`,`copy`.`url`,`notes`,`size`,"
            . "`site`.`name`,`site`.`url` AS `site_url`,`site`.`description`,"
            . "`site`.`copy_base`,`site`.`low`,`copy`.`md5`,`copy`.`amend_serial`,"
            . "`copy`.`credits`,`copy_id`"
            . " FROM `copy`,`site`"
            . " WHERE `copy`.`site`=`site`.`site_id` AND `pub`=123"
            . " ORDER BY `site`.`display_order`,`site`.`site_id`";
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn(
            DatabaseTester::createResultRowsForColumns(
            array('format', 'url', 'notes', 'size', 'name', 'site_url', 'description', 'copy_base', 'low', 'md5', 'amend_serial', 'credits', 'copy_id'),
            array(
                array('PDF', 'http://bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf', NULL, 25939827, 'bitsavers', 'http://bitsavers.org/', "Al Kossow's Bitsavers", 'http://bitsavers.org/pdf/', 'N', '0f91ba7f8d99ce7a9b57f9fdb07d3561', 7, NULL, 10277)
                )));
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $copies = $this->_manxDb->getCopiesForPub($pubId);

        $this->assertArrayHasLength($copies, 1);
        $this->assertEquals('http://bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf', $copies[0]['url']);
    }

    public function testGetDetailsForPub()
    {
        $pubId = 3;
        $query = 'SELECT `pub_id`, `company`.`name`, '
            . 'IFNULL(`ph_part`, "") AS `ph_part`, `ph_pub_date`, '
            . '`ph_title`, IFNULL(`ph_abstract`, "") AS `ph_abstract`, '
            . 'IFNULL(`ph_revision`, "") AS `ph_revision`, `ph_ocr_file`, '
            . '`ph_cover_image`, `ph_lang`, `ph_keywords` '
            . 'FROM `pub` '
            . 'JOIN `pub_history` ON `pub`.`pub_history`=`ph_id` '
            . 'JOIN `company` ON `ph_company`=`company`.`id` '
            . 'WHERE 1=1 AND `pub_id`=3';
        $rows = DatabaseTester::createResultRowsForColumns(
            array('pub_id', 'name', 'ph_part', 'ph_pub_date', 'ph_title', 'ph_abstract', 'ph_revision', 'ph_ocr_file', 'ph_cover_image', 'ph_lang', 'ph_keywords'),
            array(array(3, 'Digital Equipment Corporation', 'AA-K336A-TK', NULL, 'GIGI/ReGIS Handbook', NULL, '', NULL, 'gigi_regis_handbook.png', '+en', 'VK100')));
        $this->_statement->expects($this->once())->method('fetch')->willReturn($rows[0]);
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $details = $this->_manxDb->getDetailsForPub($pubId);

        $this->assertEquals($rows[0], $details);
    }

    public function testSearchForPublications()
    {
        $rows = array(
            array('pub_id' => 1, 'ph_part' => '', 'ph_title' => '', 'pub_has_online_copies' => '',
                'ph_abstract' => '', 'pub_has_toc' => '', 'pub_superseded' => '',
                'ph_pub_date' => '', 'ph_revision' => '', 'ph_company' => '', 'ph_alt_part' => '',
                'ph_pub_type' => '')
            );
        $keywords = array('graphics', 'terminal');
        $matchClause = ManxDatabase::matchClauseForSearchWords($keywords);
        $company = 1;
        $query = "SELECT `pub_id`, `ph_part`, `ph_title`,"
            . " `pub_has_online_copies`, `ph_abstract`, `pub_has_toc`,"
            . " `pub_superseded`, `ph_pub_date`, `ph_revision`,"
            . " `ph_company`, `ph_alt_part`, `ph_pub_type` FROM `pub`"
            . " JOIN `pub_history` ON `pub`.`pub_history` = `ph_id`"
            . " WHERE `pub_has_online_copies` $matchClause"
            . " AND `ph_company`=$company"
            . " ORDER BY `ph_sort_part`, `ph_pub_date`, `pub_id`";
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn($rows);
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $pubs = $this->_manxDb->searchForPublications($company, $keywords, true);

        $this->assertEquals($rows, $pubs);
    }

    public function testGetPublicationsSupersededByPub()
    {
        $pubId = 6105;
        $query = sprintf('SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` FROM `supersession`' .
            ' JOIN `pub` ON (`old_pub`=`pub_id` AND `new_pub`=%d)' .
            ' JOIN `pub_history` ON `pub_history`=`ph_id`', $pubId);
        $rows = array(array('ph_company' => 1, 'ph_pub' => 23, 'ph_part' => 'EK-11024-TM-PRE', 'ph_title' => 'PDP-11/24 System Technical Manual'));
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn($rows);
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $pubs = $this->_manxDb->getPublicationsSupersededByPub($pubId);

        $this->assertEquals($rows, $pubs);
    }

    public function testGetPublicationsSupersedingPub()
    {
        $pubId = 23;
        $query = sprintf('SELECT `ph_company`,`ph_pub`,`ph_part`,`ph_title` FROM `supersession`'
            . ' JOIN `pub` ON (`new_pub`=`pub_id` AND `old_pub`=%d)'
            . ' JOIN `pub_history` ON `pub_history`=`ph_id`', $pubId);
        $rows = array(array('ph_company' => 1, 'ph_pub' => 6105, 'ph_part' => 'EK-11024-TM-001', 'ph_title' => 'PDP-11/24 System Technical Manual'));
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn($rows);
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $pubs = $this->_manxDb->getPublicationsSupersedingPub($pubId);

        $this->assertEquals($rows, $pubs);
    }

    public function testAddCopy()
    {
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
        $update = 'UPDATE `pub` SET `pub_has_online_copies`=1 WHERE `pub_id`=?';
        $this->_db->expects($this->exactly(2))->method('execute')->withConsecutive(
            [ $query, array($pubId, $format, $siteId, $url, $notes, $size, $md5, $credits, $amendSerial) ],
            [ $update, array($pubId) ]
        );
        $newCopyId = 55;
        $this->_db->expects($this->once())->method('getLastInsertId')->willReturn($newCopyId);

        $result = $this->_manxDb->addCopy($pubId, $format, $siteId, $url,
                $notes, $size, $md5, $credits, $amendSerial);

        $this->assertEquals($newCopyId, $result);
    }

    public function testGetMostRecentDocuments()
    {
        $count = 200;
        $query = sprintf('SELECT `ph_pub`, `ph_company`, `ph_created`, `ph_title`, '
            . '`company`.`name` AS `company_name`, `company`.`short_name` AS `company_short_name`, '
            . '`ph_part`, `ph_revision`, `ph_keywords`, `ph_pub_date`, '
            . 'IFNULL(`ph_abstract`, "") AS `ph_abstract` '
            . 'FROM `pub_history`, `company` '
            . 'WHERE `pub_history`.`ph_company` = `company`.`id` '
            . 'ORDER BY `ph_created` DESC LIMIT 0,%d', $count);
        $this->_db->expects($this->once())->method('execute')->with($query)->willReturn(array());

        $rows = $this->_manxDb->getMostRecentDocuments(200);

        $this->assertEquals(array(), $rows);
    }

    public function testGetManxVersion()
    {
        $query = "SELECT `value` FROM `properties` WHERE `name`='version'";
        $this->_statement->expects($this->once())->method('fetch')->willReturn(array('value' => '2'));
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);

        $version = $this->_manxDb->getManxVersion();

        $this->assertEquals('2', $version);
    }

    public function testCopyExistsForUrlReturnsTrueWhenDatabaseContainsUrl()
    {
        $url = 'http://bitsavers.org/pdf/sgi/iris/IM1_Schematic.pdf';
        $this->_db->expects($this->once())->method('execute')
            ->with('SELECT `ph_company`,`ph_pub`,`ph_title` FROM `copy`,`pub_history` WHERE `copy`.`pub`=`pub_history`.`ph_pub` AND `copy`.`url`=?',
                array($url))
            ->willReturn(DatabaseTester::createResultRowsForColumns(
                array('ph_company', 'ph_pub', 'ph_title'),
                array(array('1', '2', 'IM1 Schematic'))));

        $row = $this->_manxDb->copyExistsForUrl($url);

        $this->assertEquals(3, count($row));
        $this->assertEquals('1', $row['ph_company']);
        $this->assertEquals('2', $row['ph_pub']);
        $this->assertEquals('IM1 Schematic', $row['ph_title']);
    }

    public function testCopyExistsForUrlReturnsFalseWhenDatabaseOmitsUrl()
    {
        $this->_db->expects($this->once())->method('execute')->willReturn(array());

        $this->assertFalse($this->_manxDb->copyExistsForUrl('http://bitsavers.org/pdf/sgi/iris/IM1_Schematic.pdf'));
    }

    public function testGetZeroSizeDocuments()
    {
        $query = "SELECT `copy_id`,`ph_company`,`ph_pub`,`ph_title` "
            . "FROM `copy`,`pub_history` "
            . "WHERE `copy`.`pub`=`pub_history`.`ph_pub` "
            . "AND (`copy`.`size` IS NULL OR `copy`.`size` = 0) "
            . "AND `copy`.`format` <> 'HTML' "
            . " LIMIT 0,10";
        $this->_db->expects($this->once())->method('query')->with($query)->willReturn($this->_statement);
        $this->_statement->expects($this->once())->method('fetchAll')
            ->willReturn(DatabaseTester::createResultRowsForColumns(
                array('copy_id', 'ph_company', 'ph_pub', 'ph_title'),
                array(array('66', '1', '2', 'IM1 Schematic'))
            ));

        $rows = $this->_manxDb->getZeroSizeDocuments();

        $this->assertEquals(1, count($rows));
        $this->assertEquals(66, $rows[0]['copy_id']);
        $this->assertEquals(1, $rows[0]['ph_company']);
        $this->assertEquals(2, $rows[0]['ph_pub']);
        $this->assertEquals('IM1 Schematic', $rows[0]['ph_title']);
    }

    public function testGetUrlForCopy()
    {
        $query = "SELECT `url` FROM `copy` WHERE `copy_id` = ?";
        $url = 'http://www.example.com/foo.pdf';
        $rows = DatabaseTester::createResultRowsForColumns(
            array('url'),
            array(array($url)));
        $copyId = 5;
        $this->_db->expects($this->once())->method('execute')
            ->with($query, array($copyId))
            ->willReturn($rows);

        $actualUrl = $this->_manxDb->getUrlForCopy($copyId);

        $this->assertEquals($url, $actualUrl);
    }

    public function testUpdateSizeForCopy()
    {
        $query = "UPDATE `copy` SET `size` = ? WHERE `copy_id` = ?";
        $copyId = 5;
        $size = 4096;
        $this->_db->expects($this->once())->method('execute')
            ->with($query, array($size, $copyId));

        $this->_manxDb->updateSizeForCopy($copyId, $size);
    }

    public function testUpdateMD5ForCopy()
    {
        $query = "UPDATE `copy` SET `md5` = ? WHERE `copy_id` = ?";
        $copyId = 5;
        $md5 = 'e7e98fb955892f73507d7b3a1874f9ee';
        $this->_db->expects($this->once())->method('execute')
            ->with(
                $query,
                array($md5, $copyId)
            );

        $this->_manxDb->updateMD5ForCopy($copyId, $md5);
    }

    public function testGetMissingMD5Documents()
    {
        $query = "SELECT `copy_id`,`ph_company`,`ph_pub`,`ph_title` "
            . "FROM `copy`,`pub_history` "
            . "WHERE `copy`.`pub`=`pub_history`.`ph_pub` "
            . "AND (`copy`.`md5` IS NULL) "
            . "AND `copy`.`format` <> 'HTML' "
            . " LIMIT 0,10";
        $this->_db->expects($this->once())->method('query')
            ->with($query)->willReturn($this->_statement);
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn(
            DatabaseTester::createResultRowsForColumns(
                array('copy_id', 'ph_company', 'ph_pub', 'ph_title'),
                array(array('66', '1', '2', 'IM1 Schematic'))
            ));

        $rows = $this->_manxDb->getMissingMD5Documents();

        $this->assertEquals(1, count($rows));
        $this->assertEquals(66, $rows[0]['copy_id']);
        $this->assertEquals(1, $rows[0]['ph_company']);
        $this->assertEquals(2, $rows[0]['ph_pub']);
        $this->assertEquals('IM1 Schematic', $rows[0]['ph_title']);
    }

    public function testGetProperty()
    {
        $query = "SELECT `value` FROM `properties` WHERE `name` = ?";
        $this->_db->expects($this->once())->method('execute')
            ->with($query)->willReturn(array(array('value' => 'bar')));

        $value = $this->_manxDb->getProperty('foo');

        $this->assertEquals('bar', $value);
    }

    public function testSetProperty()
    {
        $query = "INSERT INTO `properties`(`name`, `value`) VALUES (?, ?) "
            . "ON DUPLICATE KEY UPDATE `value` = ?";
        $this->_db->expects($this->once())->method('execute')
            ->with($query, array('foo', 'bar', 'bar'));

        $this->_manxDb->setProperty('foo', 'bar');
    }

    public function testAddBitSaversUnknownPath()
    {
        $select = "SELECT `site_id` FROM `site` WHERE `name`=?";
        $insert = "INSERT INTO `site_unknown`(`site`,`path`) VALUES (?,?)";
        $this->_db->expects($this->exactly(2))->method('execute')
            ->withConsecutive(
                [ $select, array('bitsavers') ],
                [ $insert, array(3, 'foo/frob.jpg') ]
            )
            ->willReturn(
                DatabaseTester::createResultRowsForColumns(array('site_id'), array(array(3))),
                null
            );

        $this->_manxDb->addSiteUnknownPath('bitsavers', 'foo/frob.jpg');
    }

    public function testIgnoreSitePath()
    {
        $select = "SELECT `site_id` FROM `site` WHERE `name`=?";
        $update = "UPDATE `site_unknown` SET `ignored`=1 WHERE `site_id`=? AND `path`=?";
        $this->_db->expects($this->exactly(2))->method('execute')
            ->withConsecutive(
                [ $select, array('bitsavers') ],
                [ $update, array(3, 'foo/frob.jpg') ]
            )
            ->willReturn(
                DatabaseTester::createResultRowsForColumns(array('site_id'), array(array(3))),
                null
            );

        $this->_manxDb->ignoreSitePath('bitsavers', 'foo/frob.jpg');
    }

    public function testGetSiteUnknownPathsOrderedById()
    {
        $select = "SELECT `site_id` FROM `site` WHERE `name`=?";
        $query = "SELECT `path`,`id` FROM `site_unknown` WHERE `site_id`=? AND `ignored`=0 ORDER BY `id` ASC LIMIT 0, 10";
        $path1 = 'foo/bar.jpg';
        $path2 = 'foo/foo.jpg';
        $this->_db->expects($this->exactly(2))->method('execute')
            ->withConsecutive(
                [ $select, array('bitsavers') ],
                [ $query, array(3) ]
            )
            ->willReturn(
                DatabaseTester::createResultRowsForColumns(
                    array('site_id'),
                    array(array(3))
                ),
                DatabaseTester::createResultRowsForColumns(
                    array('path', 'id', 'site_id'),
                    array(
                        array($path1, '1', '3'), array($path2, '2', '3')
                    )
                )
            );

        $paths = $this->_manxDb->getSiteUnknownPathsOrderedById('bitsavers', 0, true);

        $this->assertEquals( array(
                array('path' => $path1, 'site_id' => '3', 'id' => '1'),
                array('path' => $path2, 'id' => '2', 'site_id' => '3')),
            $paths);
    }

    public function testGetSiteUnknownPathsOrderedByPath()
    {
        $select = "SELECT `site_id` FROM `site` WHERE `name`=?";
        $query = "SELECT `path`,`id` FROM `site_unknown` WHERE `site_id`=? AND `ignored`=0 ORDER BY `path` ASC LIMIT 0, 10";
        $path1 = 'foo/foo.jpg';
        $path2 = 'foo/bar.jpg';
        $this->_db->expects($this->exactly(2))->method('execute')
            ->withConsecutive(
                [ $select, array('bitsavers') ],
                [ $query, array(3) ]
            )
            ->willReturn(
                DatabaseTester::createResultRowsForColumns(
                    array('site_id'), array(array(3))),
                DatabaseTester::createResultRowsForColumns(
                    array('path', 'id', 'site_id'), array(array($path2, '2', '3'), array($path1, '1', '3')))
            );

        $paths = $this->_manxDb->getSiteUnknownPathsOrderedByPath('bitsavers', 0, true);

        $this->assertEquals( array(
                array('path' => $path2, 'id' => '2', 'site_id' => '3'),
                array('path' => $path1, 'site_id' => '3', 'id' => '1')),
            $paths);
    }

    public function testSiteIgnoredPathTrue()
    {
        $select = "SELECT `site_id` FROM `site` WHERE `name`=?";
        $query = "SELECT COUNT(*) AS `count` FROM `site_unknown` WHERE `site_id`=? AND `path`=? AND `ignored`=1";
        $this->_db->expects($this->exactly(2))->method('execute')
            ->withConsecutive(
                [ $select, array('bitsavers') ],
                [ $query, array(3, 'foo/bar.jpg') ]
            )
            ->willReturn(
                DatabaseTester::createResultRowsForColumns(
                    array('site_id'), array(array(3))),
                DatabaseTester::createResultRowsForColumns(
                    array('count'), array(array(1)))
            );

        $ignored = $this->_manxDb->siteIgnoredPath('bitsavers', 'foo/bar.jpg');

        $this->assertTrue($ignored);
    }

    public function testSiteIgnoredPathFalse()
    {
        $select = "SELECT `site_id` FROM `site` WHERE `name`=?";
        $query = 'SELECT COUNT(*) AS `count` FROM `site_unknown` WHERE `site_id`=? AND `path`=? AND `ignored`=1';
        $this->_db->expects($this->exactly(2))->method('execute')
            ->withConsecutive(
                [ $select, array('bitsavers') ],
                [ $query, array(3, 'foo/bar.jpg') ]
            )
            ->willReturn(
                DatabaseTester::createResultRowsForColumns(
                    array('site_id'), array(array(3))),
                DatabaseTester::createResultRowsForColumns(
                    array('count'), array(array(0)))
            );

        $ignored = $this->_manxDb->siteIgnoredPath('bitsavers', 'foo/bar.jpg');

        $this->assertFalse($ignored);
    }

    public function testAddPubHistory()
    {
        $user = 2;
        $publicationType = '';
        $company = 10;
        $part = '070-10-1100';
        $altPart = '070-10-1101';
        $revision = '';
        $pubDate = '1976-10-31';
        $title = 'Maintenance manual for the Frobnicator';
        $keywords = 'frobnicator';
        $notes = 'Only manual known to exist.';
        $abstract = 'This manual contains maintenance procedures for the frobnicator.';
        $languages = '+en';
        $this->_db->expects($this->once())->method('execute')
            ->with('INSERT INTO `pub_history`(`ph_created`, `ph_edited_by`, `ph_pub`, '
                . '`ph_pub_type`, `ph_company`, `ph_part`, `ph_alt_part`, '
                . '`ph_revision`, `ph_pub_date`, `ph_title`, `ph_keywords`, '
                . '`ph_notes`, `ph_abstract`, `ph_lang`, '
                . '`ph_match_part`, `ph_match_alt_part`, `ph_sort_part`) '
                . 'VALUES (now(), ?, 0, '
                . '?, ?, ?, ?, '
                . '?, ?, ?, ?, '
                . '?, ?, ?, '
                . '?, ?, ?)',
                array(
                    $user,
                    $publicationType, $company, $part, $altPart,
                    $revision, $pubDate, $title, $keywords,
                    $notes, $abstract, $languages,
                    ManxDatabase::normalizePartNumber($part),
                        ManxDatabase::normalizePartNumber($altPart),
                        ManxDatabase::sortPartNumber($company, $part)
                )
            );

        $this->_manxDb->addPubHistory($user, $publicationType, $company,
            $part, $altPart, $revision, $pubDate, $title,
            $keywords, $notes, $abstract, $languages);
    }

    public function testAddSupersession()
    {
        $insert = 'INSERT INTO `supersession`(`old_pub`,`new_pub`) VALUES (?,?)';
        $query = 'UPDATE `pub` SET `pub_superseded` = 1 WHERE `pub_id` = ?';
        $oldPub = 213;
        $newPub = 563;
        $this->_db->expects($this->once())->method('getLastInsertId')->willReturn(969);
        $this->_db->expects($this->exactly(2))->method('execute')
            ->withConsecutive(
                [ $insert, array($oldPub, $newPub) ],
                [ $query, array($oldPub) ]
            )
            ->willReturn(959, null);

        $result = $this->_manxDb->addSupersession($oldPub, $newPub);

        $this->assertEquals(969, $result);
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
}
