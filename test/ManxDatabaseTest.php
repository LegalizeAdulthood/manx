<?php

require_once __DIR__ . '/../vendor/autoload.php';

class ManxDatabaseTest extends PHPUnit\Framework\TestCase
{
    /** @var Manx\IDatabase */
    private $_db;
    /** @var Manx\ManxDatabase */
    private $_manxDb;
    /** @var PDOStatement */
    private $_statement;

    protected function setUp(): void
    {
        $this->_statement = $this->createMock(PDOStatement::class);
        $this->_db = $this->createMock(Manx\IDatabase::class);
        $this->_manxDb = Manx\ManxDatabase::getInstanceForDatabase($this->_db);
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
            ->willReturn(\Manx\Test\RowFactory::createResultRowsForColumns(
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
            \Manx\Test\RowFactory::createResultRowsForColumns(array('tag_text'),
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
            \Manx\Test\RowFactory::createResultRowsForColumns(
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
        //     \Manx\Test\RowFactory::createResultRowsForColumns(array('html_text'),
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
            \Manx\Test\RowFactory::createResultRowsForColumns(
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
            \Manx\Test\RowFactory::createResultRowsForColumns(
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
            \Manx\Test\RowFactory::createResultRowsForColumns(
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
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(array('mirror_url'),
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
            . " AND `site`.`live`='Y'"
            . " ORDER BY `site`.`display_order`,`site`.`site_id`";
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn(
            \Manx\Test\RowFactory::createResultRowsForColumns(
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
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
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
        $matchClause = Manx\ManxDatabase::matchClauseForSearchWords($keywords);
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
        $this->_db->expects($this->once())->method('beginTransaction');
        $update = 'UPDATE `pub` SET `pub_has_online_copies`=1 WHERE `pub_id`=?';
        $this->_db->expects($this->exactly(2))->method('execute')->withConsecutive(
            [ $query, array($pubId, $format, $siteId, $url, $notes, $size, $md5, $credits, $amendSerial) ],
            [ $update, array($pubId) ]
        );
        $newCopyId = 55;
        $this->_db->expects($this->once())->method('getLastInsertId')->willReturn($newCopyId);
        $this->_db->expects($this->once())->method('commit');

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
            ->willReturn(\Manx\Test\RowFactory::createResultRowsForColumns(
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
            ->willReturn(\Manx\Test\RowFactory::createResultRowsForColumns(
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
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
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
        $query = "SELECT `copy_id`,`ph_company`,`ph_pub`,`ph_title`,`url` "
            . "FROM `copy`,`pub_history` "
            . "WHERE `copy`.`pub`=`pub_history`.`ph_pub` "
            . "AND (`copy`.`md5` IS NULL) "
            . "AND `copy`.`format` <> 'HTML' "
            . " LIMIT 0,10";
        $this->_db->expects($this->once())->method('query')
            ->with($query)->willReturn($this->_statement);
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url'],
            [
                ['66', '1', '2', 'IM1 Schematic', 'http://bitsavers.org/pdf/dec/IM1_Schematic.pdf' ]
            ]);
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn($rows);

        $result = $this->_manxDb->getMissingMD5Documents();

        $this->assertEquals($rows, $result);
    }

    public function testGetAllMissingMD5Documents()
    {
        $query = "SELECT `copy_id`,`ph_company`,`ph_pub`,`ph_title`,`url` "
            . "FROM `copy`,`pub_history` "
            . "WHERE `copy`.`pub`=`pub_history`.`ph_pub` "
            . "AND (`copy`.`md5` IS NULL) "
            . "AND `copy`.`format` <> 'HTML'";
        $this->_db->expects($this->once())->method('query')
            ->with($query)->willReturn($this->_statement);
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['copy_id', 'ph_company', 'ph_pub', 'ph_title', 'url'],
            [
                ['66', '1', '2', 'IM1 Schematic', 'http://bitsavers.org/pdf/dec/IM1_Schematic.pdf' ]
            ]);
        $this->_statement->expects($this->once())->method('fetchAll')->willReturn($rows);

        $result = $this->_manxDb->getAllMissingMD5Documents();

        $this->assertEquals($rows, $result);
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

    public function testAddSiteUnknownPaths()
    {
        $this->_db->expects($this->once())->method('beginTransaction');
        $selectSite = "SELECT `site_id` FROM `site` WHERE `name`=?";
        $siteId = 3;
        $insertSUD = "INSERT INTO `site_unknown_dir`(`site_id`, `path`) VALUES (3, ?), (3, ?), (3, ?) ON DUPLICATE KEY UPDATE `site_id` = VALUES(`site_id`)";
        $selectSUD = "SELECT `id`, `path`, `parent_dir_id` FROM `site_unknown_dir` WHERE `site_id` = 3 AND `path` IN (?, ?, ?)";
        $updateSUD = "UPDATE `site_unknown_dir` SET `parent_dir_id` = ? WHERE `id` = ?";
        $dirId1 = 10;
        $dirId2 = 12;
        $dirId3 = 14;
        $insertSU = "INSERT INTO `site_unknown`(`site_id`, `path`, `dir_id`) VALUES (3, ?, ?), (3, ?, ?), (3, ?, ?) ON DUPLICATE KEY UPDATE `site_id` = VALUES(`site_id`)";
        $this->_db->expects($this->exactly(5))->method('execute')
            ->withConsecutive(
                [$selectSite, ['bitsavers']],
                [$insertSUD, ['foo/DEC', 'foo', 'bar']],
                [$selectSUD, ['foo/DEC', 'foo', 'bar']],
                [$updateSUD, [$dirId2, $dirId1]],
                [$insertSU, ['IndexByDate.txt', -1, 'frob.jpg', $dirId1, 'bar.pdf', $dirId3]]
            )
            ->willReturn(
                \Manx\Test\RowFactory::createResultRowsForColumns(['site_id'], [[3]]),
                null,
                \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'path', 'parent_dir_id'],
                    [
                    [$dirId1, 'foo/DEC', -1],
                    [$dirId2, 'foo', -1],
                    [$dirId3, 'bar', -1]
                    ]),
                null,
                null
            );
        $this->_db->expects($this->once())->method('commit');

        $this->_manxDb->addSiteUnknownPaths('bitsavers', ['IndexByDate.txt', 'foo/DEC/frob.jpg', 'bar/bar.pdf']);
    }

    public function testIgnoreSitePath()
    {
        $ignoredIds = [56, 111];
        $update = "UPDATE `site_unknown` `su` SET `su`.`ignored` = 1 WHERE `su`.`id` in (?, ?)";
        $this->_db->expects($this->exactly(1))->method('execute')->with($update, $ignoredIds);

        $this->_manxDb->ignoreSitePaths($ignoredIds);
    }

    public function testGetSiteUnknownPathsOrderedById()
    {
        $siteName = 'bitsavers';
        $query = "SELECT `su`.`id`, CONCAT(`sud`.`path`, '/', `su`.`path`) AS `path` "
            . "FROM `site_unknown` `su`, `site_unknown_dir` `sud`, `site` `s` "
            . "WHERE `s`.`name` = ? "
            . "AND `s`.`site_id` = `su`.`site_id` "
            . "AND `s`.`site_id` = `sud`.`site_id` "
            . "AND `su`.`dir_id` = `sud`.`id` "
            . "AND `su`.`ignored` = 0 "
            . "ORDER BY `id` ASC "
            . "LIMIT 0, 10";
        $path1 = 'foo/bar.jpg';
        $path2 = 'foo/foo.jpg';
        $this->_db->expects($this->exactly(1))->method('execute')
            ->with($query, [$siteName])
            ->willReturn(\Manx\Test\RowFactory::createResultRowsForColumns(
                    ['id', 'path'],
                    [['1', $path1], ['2', $path2]]
                ));

        $paths = $this->_manxDb->getSiteUnknownPathsOrderedById($siteName, 0, true);

        $this->assertEquals([
                ['id' => '1', 'path' => $path1],
                ['id' => '2', 'path' => $path2]
            ],
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
                \Manx\Test\RowFactory::createResultRowsForColumns(
                    array('site_id'), array(array(3))),
                \Manx\Test\RowFactory::createResultRowsForColumns(
                    array('path', 'id', 'site_id'), array(array($path2, '2', '3'), array($path1, '1', '3')))
            );

        $paths = $this->_manxDb->getSiteUnknownPathsOrderedByPath('bitsavers', 0, true);

        $this->assertEquals( array(
                array('path' => $path2, 'id' => '2', 'site_id' => '3'),
                array('path' => $path1, 'site_id' => '3', 'id' => '1')),
            $paths);
    }

    public function testgetSiteUnknownPathCount()
    {
        $siteName = 'bitsavers';
        $select = "SELECT COUNT(`su`.`id`) AS `count` "
            . "FROM `site_unknown` `su`, `site` `s` "
            . "WHERE `s`.`name` = ? "
                . "AND `s`.`site_id` = `su`.`site_id` "
                . "AND `su`.`ignored` = 0";
        $count = 10;
        $this->_db->expects($this->once())->method('execute')
            ->with($select, [$siteName])
            ->willReturn([['count' => $count]]);

        $results = $this->_manxDb->getSiteUnknownPathCount($siteName);

        $this->assertEquals($count, $results);
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
                    Manx\ManxDatabase::normalizePartNumber($part),
                    Manx\ManxDatabase::normalizePartNumber($altPart),
                    Manx\ManxDatabase::sortPartNumber($company, $part)
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
        $this->_db->expects($this->once())->method('beginTransaction');
        $this->_db->expects($this->once())->method('getLastInsertId')->willReturn(969);
        $this->_db->expects($this->exactly(2))->method('execute')
            ->withConsecutive(
                [ $insert, array($oldPub, $newPub) ],
                [ $query, array($oldPub) ]
            )
            ->willReturn(959, null);
        $this->_db->expects($this->once())->method('commit');

        $result = $this->_manxDb->addSupersession($oldPub, $newPub);

        $this->assertEquals(969, $result);
    }

    public function testAddCompany()
    {
        $this->_db->expects($this->once())->method('execute')
            ->with('INSERT INTO `company`(`name`,`short_name`,`sort_name`,`display`,`notes`) VALUES (?,?,?,?,?)',
                array('Digital Equipment Corporation', 'DEC', 'dec', 'Y', 'notes'));

        $this->_manxDb->addCompany('Digital Equipment Corporation', 'DEC', 'dec', true, 'notes');
    }

    public function testUpdateCompany()
    {
        $this->_db->expects($this->once())->method('execute')
            ->with('UPDATE `company` SET `name`=?, `short_name`=?, `sort_name`=?, `display`=?, `notes`=? WHERE `id`=?',
                array('Digital Equipment Corporation', 'DEC', 'dec', 'Y', 'notes', 66));

        $this->_manxDb->updateCompany(66, 'Digital Equipment Corporation', 'DEC', 'dec', true, 'notes');
    }

    public function testRemoveUnknownPathsWithCopy()
    {
        $this->_db->expects($this->once())->method('beginTransaction');
        $purgeSuCopies = "CALL `manx_purge_su_copies`()";
        $purgeDirs = "CALL `manx_purge_unused_unknown_directories`()";
        $this->_db->expects($this->exactly(2))->method('execute')->withConsecutive(
            [$purgeSuCopies, []],
            [$purgeDirs, []]);
        $this->_db->expects($this->once())->method('commit');

        $this->_manxDb->removeUnknownPathsWithCopy();
    }

    public function testGetUnknownPathsForKnownCompanies()
    {
        $siteName = 'bitsavers';
        $select =
            "SELECT `su`.`id`, `su`.`site_id`, `scd`.`company_id`, "
                . "CONCAT(`s`.`copy_base`, `sud`.`path`, '/', `su`.`path`) AS `url` "
            . "FROM "
                . "`site` `s`, "
                . "`site_unknown` `su`, "
                . "`site_company_dir` `scd`, "
                . "`site_unknown_dir` `sud` "
            . "WHERE "
                . "`s`.`name` = ? "
                . "AND `s`.`live` = 'Y' "
                . "AND `s`.`site_id` = `su`.`site_id` "
                . "AND `s`.`site_id` = `scd`.`site_id` "
                . "AND `s`.`site_id` = `sud`.`site_id` "
                . "AND `su`.`dir_id` = `sud`.`id` "
                . "AND `su`.`scanned` = 0 "
                . "AND `su`.`ignored` = 0 "
                . "AND INSTR(`su`.`path`, '#') = 0 "
                . "AND INSTR(`su`.`path`, ' ') = 0 "
                . "AND INSTR(`su`.`path`, '&') = 0 "
                . "AND INSTR(`su`.`path`, '%') = 0 "
                . "AND `su`.`path` LIKE '%\_%\_%.pdf' "
                . "AND ("
                    . "(`scd`.`parent_directory` = '' AND `sud`.`path` LIKE CONCAT(`scd`.`directory`, '/%')) "
                    . "OR "
                    . "(`scd`.`parent_directory` <> '' AND `sud`.`path` LIKE CONCAT(`scd`.`parent_directory`, '/', `scd`.`directory`, '/%'))"
                    . ") "
            . "ORDER BY `su`.`id`";
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'company_id', 'url'],
            [
                [7766, 3, 13, 'http://bitsavers.org/pdf/dec/foo/EK-3333-01_Jumbotron_Users_Guide.pdf'],
                [7767, 3, 13, 'http://bitsavers.org/pdf/dec/foo/EK-6666-01_Jumbotron_Reference_Manual.pdf']
            ]);
        $this->_db->expects($this->once())->method('execute')->with($select, [$siteName])->willReturn($rows);

        $results = $this->_manxDb->getUnknownPathsForCompanies($siteName);

        $this->assertEquals($rows, $results);
    }

    public function testMarkUnknownPathScanned()
    {
        $unknownId = 13;
        $this->_db->expects($this->once())->method('execute')->with('UPDATE `site_unknown` SET `scanned` = 1 WHERE `id` = ?', array($unknownId));

        $this->_manxDb->markUnknownPathScanned($unknownId);
    }

    public function testGetAllSiteUnknownPaths()
    {
        $siteName = 'bitsavers';
        $select = "SELECT `su`.`id`, CONCAT(`sud`.`path`, '/', `su`.`path`) AS `path` "
            . "FROM `site_unknown` `su`, `site_unknown_dir` `sud`, `site` `s` "
                . "WHERE `s`.`name` = ? "
                    . "AND `s`.`site_id` = `su`.`site_id` "
                    . "AND `s`.`site_id` = `sud`.`site_id` " 
                    . "AND `su`.`dir_id` = `sud`.`id` "
            . "ORDER BY `su`.`id`";
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'path'],
            [
                [1, 'foo/bar.pdf']
            ]);
        $this->_db->expects($this->once())->method('execute')->with($select, [$siteName])->willReturn($rows);

        $results = $this->_manxDb->getAllSiteUnknownPaths($siteName);

        $this->assertEquals($rows, $results);
    }

    public function testRemoveSiteUnknownPathById()
    {
        $id = 10;
        $delete = "DELETE FROM `site_unknown` WHERE `id` = ?";
        $this->_db->expects($this->once())->method('execute')->with($delete, [$id]);

        $this->_manxDb->removeSiteUnknownPathById($id);
    }

    public function testGetPossiblyMovedSiteUnknownPaths()
    {
        $siteName = 'bitsavers';
        $select = "SELECT CONCAT(`sud`.`path`, '/', `su`.`path`) AS `path`, `su`.`id` AS `path_id`, `c`.`url`, `c`.`copy_id`, `c`.`size`, `c`.`md5` "
            . "FROM `copy` `c`, `site` `s`, `site_unknown` `su`, `site_unknown_dir` `sud` "
            . "WHERE `s`.`name` = ? "
            . "AND `s`.`site_id` = `c`.`site` "
            . "AND `s`.`site_id` = `su`.`site_id` "
            . "AND `s`.`site_id` = `sud`.`site_id` "
            . "AND `su`.`dir_id` = `sud`.`id` "
            . "AND ((`c`.`sud_id` <> -1 AND `su`.`dir_id` <> `c`.`sud_id`) "
                . "OR (`c`.`sud_id` = -1 AND `c`.`url` <> CONCAT(`s`.`copy_base`, `sud`.`path`, '/', `su`.`path`))) "
            . "AND SUBSTRING_INDEX(`c`.`url`, '/', -1) = `su`.`path`";
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(['path', 'path_id', 'url', 'copy_id', 'md5'],
            [
                ['foo/bar/foo.pdf', 11, 'http://bitsavers.org/pdf/foo/bar/foo.pdf', 22, 6566, 'd131dd02c5e6eec4']
            ]);
        $this->_db->expects($this->once())->method('execute')->with($select, [$siteName])->willReturn($rows);

        $results = $this->_manxDb->getPossiblyMovedSiteUnknownPaths($siteName);

        $this->assertEquals($results, $rows);
    }

    public function testGetSiteUnknownDir()
    {
        $dirId = 133;
        $select = "SELECT * FROM `site_unknown_dir` WHERE `id` = ?";
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex'],
            [
                [133, 3, 'dec', -1, '']
            ]);
        $this->_db->expects($this->once())->method('execute')->with($select, [$dirId])->willReturn($rows);

        $results = $this->_manxDb->getSiteUnknownDir($dirId);

        $this->assertEquals($results, $rows[0]);
    }

    public function testGetSiteUnknownDirectories()
    {
        $siteName = 'bitsavers';
        $select = "SELECT `sud`.* "
            . "FROM `site_unknown_dir` `sud`, `site` `s` "
            . "WHERE `s`.`name` = ? "
            . "AND `s`.`site_id` = `sud`.`site_id` "
            . "AND `sud`.`parent_dir_id` = ?";
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex'],
            [
                [5005, 3, 'BBS', -1, ''],
                [5006, 3, 'books', -1, ''],
            ]);
        $this->_db->expects($this->once())->method('execute')->with($select, [$siteName, -1])->willReturn($rows);

        $results = $this->_manxDb->getSiteUnknownDirectories('bitsavers', -1);

        $this->assertEquals($results, $rows);
    }

    public function testGetSiteUnknownPaths()
    {
        $siteName = 'bitsavers';
        $select = "SELECT `su`.* "
            . "FROM `site_unknown` `su`, `site_unknown_dir` `sud`, `site` `s` "
            . "WHERE `s`.`name` = ? "
            . "AND `s`.`site_id` = `su`.`site_id` "
            . "AND `s`.`site_id` = `sud`.`site_id` "
            . "AND `su`.`ignored` = 0 "
            . "AND `su`.`dir_id` = `sud`.`id` "
            . "AND `su`.`dir_id` = ? "
            . "ORDER BY `su`.`path`";
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [5005, 3, 'foo.pdf', 0, 0, -1],
                [5005, 3, 'foo.jpg', 0, 1, -1]
            ]);
        $this->_db->expects($this->once())->method('execute')->with($select, [$siteName, -1])->willReturn($rows);

        $results = $this->_manxDb->getSiteUnknownPaths('bitsavers', -1);

        $this->assertEquals($results, $rows);
    }

    public function testSiteFileMoved()
    {
        $copyId = 66;
        $pathId = 77;
        $url = 'http://bitsavers.org/pdf/new/path/to/file.pdf';
        $this->_db->expects($this->once())->method('beginTransaction');
        $deleteId = "DELETE FROM site_unknown WHERE id = ?";
        $updateUrl = "UPDATE copy SET url = ? WHERE copy_id = ?";
        $updateSudId = "UPDATE copy SET sud_id = -1 WHERE copy_id = ?";
        $this->_db->expects($this->exactly(3))->method('execute')
            ->withConsecutive(
                [$deleteId, [$pathId]],
                [$updateUrl, [$url, $copyId]],
                [$updateSudId, [$copyId]]);
        $this->_db->expects($this->once())->method('commit');


        $this->_manxDb->siteFileMoved($pathId, $copyId, $url);
    }

    public function testGetIngestionRobotUser()
    {
        $select = "SELECT `id` FROM `user` WHERE `first_name` = 'Ingestion' AND `last_name` = 'Robot'";
        $userId = 66;
        $this->_db->expects($this->once())->method('query')->with($select)->willReturn($this->_statement);
        $this->_statement->expects($this->once())->method('fetch')->willReturn(array($userId));

        $result = $this->_manxDb->getIngestionRobotUser();

        $this->assertEquals($userId, $result);
    }

    public function testGetPublicationsForPart()
    {
        $companyId = 55;
        $select = 'SELECT pub_id,ph_part,ph_title,ph_pub_date '
            . 'FROM pub '
            . 'JOIN pub_history ON pub_history = ph_id '
            . 'WHERE (ph_match_part LIKE ? OR ph_match_alt_part LIKE ?) '
            . 'AND ph_company = ? '
            . 'ORDER BY ph_sort_part, ph_pub_date '
            . 'LIMIT 10';
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
            [
                [7766, 'AA-44422', 'Jumbotron Users Guide', '1977-01'],
                [7767, 'AA-44422-02', "Jumbotron User's Guide", '1978-04']
            ]);
        $this->_db->expects($this->once())->method('execute')->with($select)->willReturn($rows);

        $result = $this->_manxDb->getPublicationsForPartNumber('AA-44422', $companyId);

        $this->assertEquals($rows, $result);
    }

    public function testAddSiteDirectory()
    {
        $siteName = 'VTDA';
        $companyId = 44;
        $directory = 'DigitalResearch';
        $parentDirectory = 'computing';
        $select = "SELECT * FROM `site_company_dir` `scd`, `site` `s` "
            . "WHERE `scd`.`site_id`=`s`.`site_id` "
            . "AND `s`.`name`=? "
            . "AND `scd`.`company_id`=?";
        $execute = "INSERT INTO `site_company_dir`(`site_id`, `company_id`, `directory`, `parent_directory`) "
            . "(SELECT `site_id`, ?, ?, ? FROM `site` WHERE `name`=?)";
        $this->_db->expects($this->exactly(2))->method('execute')->withConsecutive(
                [$select, [$siteName, $companyId]],
                [$execute, [$companyId, $directory, $parentDirectory, $siteName]])
            ->willReturn([], []);

        $this->_manxDb->addSiteDirectory($siteName, $companyId, $directory, $parentDirectory);
    }

    public function testGetCompanyForSiteDirectory()
    {
        $siteName = 'VTDA';
        $directory = 'DigitalResearch';
        $parentDirectory = 'computing';
        $select = "SELECT `scd`.`company_id` FROM `site_company_dir` `scd`, `site` `s` "
            . "WHERE `scd`.`site_id` = `s`.`site_id` "
            . "AND `s`.`name` = ? "
            . "AND `scd`.`directory` = ? "
            . "AND `scd`.`parent_directory` = ?";
        $companyId = 38;
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns([ 'company_id' ], [ [ $companyId ] ]);
        $this->_db->expects($this->once())->method('execute')->with($select, [ $siteName, $directory, $parentDirectory ])->willReturn($rows);

        $result = $this->_manxDb->getCompanyIdForSiteDirectory($siteName, $directory, $parentDirectory);

        $this->assertEquals($companyId, $result);
    }

    public function testSetSiteLive()
    {
        $execute = "UPDATE `site` SET `live`=? WHERE `site_id`=?";
        $siteId = 3;
        $this->_db->expects($this->once())->method('execute')->with($execute, [ 'Y', $siteId ]);

        $this->_manxDb->setSiteLive($siteId, true);
    }

    public function testGetSampleCopiesForSite()
    {
        $select = "SELECT `url` FROM `copy` WHERE `site` = ? AND `size` <> 0 AND `md5` <> '' LIMIT 0, 1000";
        $siteId = 3;
        $rows = \Manx\Test\RowFactory::createResultRowsForColumns([ 'url' ], [ [ 'http://bitsavers.org/pdf/dec/foo.pdf' ] ]);
        $this->_db->expects($this->once())->method('execute')->with($select, [ $siteId ])->willReturn($rows);

        $results = $this->_manxDb->getSampleCopiesForSite($siteId);

        $this->assertEquals($rows, $results);
    }

    public function testUpdateIgnoredUnkownDirs()
    {
        $call = "CALL `manx_update_unknown_dir_ignored`()";
        $this->_db->expects($this->once())->method('execute')->with($call, []);

        $this->_manxDb->updateIgnoredUnknownDirs();
    }

    public function testUpdateCopySiteUnknownDirIds()
    {
        $this->_db->expects($this->once())->method('execute')->with("CALL `manx_update_copy_sud_ids`()", []);

        $this->_manxDb->updateCopySiteUnknownDirIds();
    }

    public function testSetCopySiteUnknownDirId()
    {
        $copyId = 2066;
        $siteUnknownId = 509;
        $update = "UPDATE `copy` AS `c` "
            . "INNER JOIN `site_unknown` AS `su` "
            . "SET `c`.`sud_id` = `su`.`dir_id` "
            . "WHERE `c`.`copy_id` = ? "
            . "AND `su`.`id` = ?";
        $this->_db->expects($this->once())->method('execute')->with($update, [$copyId, $siteUnknownId]);

        $this->_manxDb->setCopySiteUnknownDirId($copyId, $siteUnknownId);
    }

    public function testupdateIgnoredUnknownSingleDir()
    {
        $siteUnknownId = 6066;
        $call = "CALL `manx_update_unknown_single_dir_ignored`(" . $siteUnknownId . ")";
        $this->_db->expects($this->once())->method('execute')->with($call, []);

        $this->_manxDb->updateIgnoredUnknownSingleDir($siteUnknownId);
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
