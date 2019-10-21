<?php

require_once 'pages/DetailsPage.php';
require_once 'test/DatabaseTester.php';

use Pimple\Container;

class RenderDetailsTester extends DetailsPage
{
    public function __construct(Container $config)
    {
        DetailsPage::__construct($config);
        $this->renderLanguageCalled = false;
        $this->renderAmendmentsCalled = false;
        $this->renderOSTagsCalled = false;
        $this->renderLongDecriptionCalled = false;
        $this->renderCitationsCalled = false;
        $this->renderSupersessionsCalled = false;
        $this->renderTableOfContentsCalled = false;
        $this->renderCopiesCalled = false;
    }

    public function renderBodyContent()
    {
        parent::renderBodyContent();
    }

    public $renderLanguageCalled, $renderLanguageLastLanguage;
    public function renderLanguage($lang)
    {
        $this->renderLanguageCalled = true;
        $this->renderLanguageLastLanguage = $lang;
    }

    public $renderAmendmentsCalled, $renderAmendmentsLastPubId;
    public function renderAmendments($pubId)
    {
        $this->renderAmendmentsCalled = true;
        $this->renderAmendmentsLastPubId = $pubId;
    }

    public $renderOSTagsCalled, $renderOSTagsLastPubId;
    public function renderOSTags($pubId)
    {
        $this->renderOSTagsCalled = true;
        $this->renderOSTagsLastPubId = $pubId;
    }

    public $renderLongDescriptionCalled, $renderLongDescriptionLastPubId;
    public function renderLongDescription($pubId)
    {
        $this->renderLongDescriptionCalled = true;
        $this->renderLongDescriptionLastPubId = $pubId;
    }

    public $renderCitationsCalled, $renderCitationsLastPubId;
    public function renderCitations($pubId)
    {
        $this->renderCitationsCalled = true;
        $this->renderCitationsLastPubId = $pubId;
    }

    public $renderSupersessionsCalled, $renderSupersessionsLastPubId;
    public function renderSupersessions($pubId)
    {
        $this->renderSupersessionsCalled = true;
        $this->renderSupersessionsLastPubId = $pubId;
    }

    public $renderTableOfContentsCalled, $renderTableOfContentsLastPubId, $renderTableOfContentsLastFullContents;
    public function renderTableOfContents($pubId, $fullContents)
    {
        $this->renderTableOfContentsCalled = true;
        $this->renderTableOfContentsLastPubId = $pubId;
        $this->renderTableOfContentsLastFullContents = $fullContents;
    }

    public $renderCopiesCalled, $renderCopiesLastPubId;
    public function renderCopies($pubId)
    {
        $this->renderCopiesCalled = true;
        $this->renderCopiesLastPubId = $pubId;
    }
}

class TestDetailsPageStatic extends PHPUnit\Framework\TestCase
{
    public function testNeatListPlainOneItem()
    {
        $this->assertEquals('English', DetailsPage::neatListPlain(array('English')));
    }

    public function testNeatListPlainTwoItems()
    {
        $this->assertEquals('English and French', DetailsPage::neatListPlain(array('English', 'French')));
    }

    public function testNeatListPlainThreeItems()
    {
        $this->assertEquals('English, French and German', DetailsPage::neatListPlain(array('English', 'French', 'German')));
    }

    public function testDetailParamsForPathInfoCompanyAndId()
    {
        $params = DetailsPage::detailParamsForPathInfo('/1,2');

        $this->assertEquals(4, count(array_keys($params)));
        $this->assertEquals(1, $params['cp']);
        $this->assertEquals(2, $params['id']);
        $this->assertEquals(1, $params['cn']);
        $this->assertEquals(0, $params['pn']);
    }

    public function testFormatDocRefNoPart()
    {
        $row = array('ph_company' => 1, 'ph_pub' => 3, 'ph_title' => 'Frobozz Electric Company Grid Adjustor & Pulminator Reference', 'ph_part' => NULL);

        $result = DetailsPage::formatDocRef($row);

        $this->assertEquals('<a href="../details.php/1,3"><cite>Frobozz Electric Company Grid Adjustor &amp; Pulminator Reference</cite></a>', $result);
    }

    public function testFormatDocRefWithPart()
    {
        $row = array('ph_company' => 1, 'ph_pub' => 3, 'ph_title' => 'Frobozz Electric Company Grid Adjustor & Pulminator Reference', 'ph_part' => 'FECGAPR');

        $result = DetailsPage::formatDocRef($row);

        $this->assertEquals('FECGAPR, <a href="../details.php/1,3"><cite>Frobozz Electric Company Grid Adjustor &amp; Pulminator Reference</cite></a>', $result);
    }

    public function testReplaceNullWithEmptyStringOrTrimForNull()
    {
        $this->assertEquals('', DetailsPage::replaceNullWithEmptyStringOrTrim(null));
    }

    public function testReplaceNullWithEmptyStringOrTrimForString()
    {
        $this->assertEquals('foo', DetailsPage::replaceNullWithEmptyStringOrTrim('foo'));
    }

    public function testReplaceNullWithEmptyStringOrTrimForWhitespace()
    {
        $this->assertEquals('foo', DetailsPage::replaceNullWithEmptyStringOrTrim(" foo\t\r\n"));
    }
}

class TestDetailsPage extends PHPUnit\Framework\TestCase
{
    /** @var IManxDatabase */
    private $_db;
    /** @var IManx */
    private $_manx;
    /** @var DetailsPage */
    private $_page;

    protected function setUp()
    {
        $_SERVER['PATH_INFO'] = '/1,3';
        $this->_db = $this->createMock(IManxDatabase::class);
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->expects($this->atLeast(1))->method('getDatabase')->willReturn($this->_db);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $this->_page = new DetailsPage($config);
    }

    public function testRenderLanguageEnglishGivesNoOutput()
    {
        $this->_db->expects($this->never())->method('getDisplayLanguage');

        $this->_page->renderLanguage('+en');

        $this->expectOutputString('');
    }

    public function testRenderLanguageFrench()
    {
        $this->_db->expects($this->once())->method('getDisplayLanguage')->with('fr')->willReturn('French');

        $this->_page->renderLanguage('+fr');

        $this->expectOutputString("<tr><td>Language:</td><td>French</td></tr>\n");
    }

    public function testRenderLanguageEnglishFrenchGerman()
    {
        $this->_db->expects($this->exactly(3))->method('getDisplayLanguage')->withConsecutive(['en'], ['fr'], ['de'])->willReturn('English', 'French', 'German');

        $this->_page->renderLanguage('+en+fr+de');

        $this->expectOutputString("<tr><td>Languages:</td><td>English, French and German</td></tr>\n");
    }

    public function testRenderAmendments()
    {
        $pubId = 3;
        $this->_db->expects($this->once())->method('getAmendmentsForPub')
            ->with($pubId)
            ->willReturn(
                DatabaseTester::createResultRowsForColumns(
                    array('ph_company', 'ph_pub', 'ph_part', 'ph_title', 'ph_pub_date'),
                    array(
                        array(1, 4496, 'DEC-15-YWZA-DN1', 'DDT (Dynamic Debugging Technique) Utility Program', '1970-04'),
                        array(1, 3301, 'DEC-15-YWZA-DN3', 'SGEN System Generator Utility Program', '1970-09')
                    )));
        $this->_db->expects($this->atLeast(1))->method('getOSTagsForPub')
            ->with($pubId)
            ->willReturn(array('RSX-11M Version 4.0', 'RSX-11M-PLUS Version 2.0'));

        $this->_page->renderAmendments($pubId);

        $this->expectOutputString('<tr valign="top"><td>Amended&nbsp;by:</td>'
            . '<td><ul class="citelist"><li>DEC-15-YWZA-DN1, <a href="../details.php/1,4496"><cite>DDT (Dynamic Debugging Technique) Utility Program</cite></a> (1970-04) <b>OS:</b> RSX-11M Version 4.0, RSX-11M-PLUS Version 2.0</li>'
            . '<li>DEC-15-YWZA-DN3, <a href="../details.php/1,3301"><cite>SGEN System Generator Utility Program</cite></a> (1970-09) <b>OS:</b> RSX-11M Version 4.0, RSX-11M-PLUS Version 2.0</li>'
            . "</ul></td></tr>\n");
    }

    public function testRenderOSTagsEmpty()
    {
        $pubId = 3;
        $this->_db->expects($this->atLeast(1))->method('getOSTagsForPub')
            ->with($pubId)
            ->willReturn(array());

        $this->_page->renderOSTags($pubId);

        $this->expectOutputString('');
    }

    public function testRenderOSTagsTwoTags()
    {
        $pubId = 3;
        $this->_db->expects($this->atLeast(1))->method('getOSTagsForPub')
            ->with($pubId)
            ->willReturn(array('RSX-11M Version 4.0', 'RSX-11M-PLUS Version 2.0'));

        $this->_page->renderOSTags($pubId);

        $this->expectOutputString("<tr><td>Operating System:</td><td>RSX-11M Version 4.0, RSX-11M-PLUS Version 2.0</td></tr>\n");
    }

    public function testRenderLongDescriptionEmpty()
    {
        $pubId = 3;
        $this->_db->expects($this->once())->method('getLongDescriptionForPub')->with($pubId)->willReturn(array());

        $this->_page->renderLongDescription($pubId);

        $this->expectOutputString('');
    }

    public function testRenderLongDescription()
    {
        $pubId = 3;
        $this->_db->expects($this->once())->method('getLongDescriptionForPub')
            ->with($pubId)
            ->willReturn(array('<p>This is paragraph one.</p>', '<p>This is paragraph two.</p>'));

        $this->_page->renderLongDescription($pubId);

        $this->expectOutputString('<tr valign="top"><td>Description:</td>'
            . "<td><p>This is paragraph one.</p><p>This is paragraph two.</p></td></tr>");
    }

    public function testRenderCitations()
    {
        $pubId = 72;
        $this->_db->expects($this->once())->method('getCitationsForPub')
            ->with($pubId)
            ->willReturn(DatabaseTester::createResultRowsForColumns(
                array('ph_company', 'ph_pub', 'ph_part', 'ph_title'),
                array(array(1, 123, 'EK-306AA-MG-001', 'KA655 CPU System Maintenance'))
            ));

        $this->_page->renderCitations($pubId);

        $this->expectOutputString('<tr valign="top"><td>Cited by:</td>'
            . '<td><ul class="citelist">'
                . '<li>EK-306AA-MG-001, <a href="../details.php/1,123"><cite>KA655 CPU System Maintenance</cite></a></li>'
            . "</ul></td></tr>\n");
    }

    public function testRenderTableOfContents()
    {
        $pubId = 123;
        $this->_db->expects($this->once())->method('getTableOfContentsForPub')
            ->with($pubId, true)
            ->willReturn(
                DatabaseTester::createResultRowsForColumns(
                    array('level', 'label', 'name'),
                    array(
                        array(1, 'Chapter 1', 'KA655 CPU and Memory Subsystem'),
                        array(2, '1.1', 'Introduction'),
                        array(2, '1.2', 'KA655 CPU Features'),
                        array(3, '1.2.1', 'Central Processing Unit (CPU)'),
                        array(3, '1.2.2', 'Clock Functions'),
                        array(3, '1.2.3', 'Floating Point Accelerator'),
                        array(3, '1.2.4', 'Cache Memory'),
                        array(3, '1.2.5', 'Memory Controller'),
                        array(3, '1.2.6', 'MicroVAX System Support Functions'),
                        array(3, '1.2.7', 'Resident Firmware'),
                        array(3, '1.2.8', 'Q22-Bus Interface'),
                        array(2, '1.3', 'KA655 Connectors'),
                        array(2, '1.4', 'H3600-SA CPU I/O Panel'),
                        array(2, '1.5', 'MS650-BA Memory'),
                        array(1, 'Chapter 2', 'Configuration'),
                        array(2, '2.1', 'Introduction'),
                        array(2, '2.2', 'General Module Order'),
                        array(3, '2.2.1', 'Module Order Rules for KA655 Systems'),
                        array(3, '2.2.2', 'Recommended Module Order for KA655 Systems'),
                        array(2, '2.3', 'Module Configuration'),
                        array(2, '2.4', 'DSSI Configuration'),
                        array(3, '2.4.1', 'Changing RF-Series ISE Parameters'),
                        array(3, '2.4.2', 'Changing the Unit Number'),
                        array(3, '2.4.3', 'Changing the Allocation Class'),
                        array(3, '2.4.4', 'DSSI Cabling'),
                        array(4, '2.4.4.1', 'DSSI Bus Termination and Length'),
                        array(3, '2.4.5', 'Dual-Host Capability'),
                        array(3, '2.4.6', 'Limitations to Dual-Host Configurations'),
                        array(2, '2.5', 'Configuration Worksheet'),
                        array(1, 'Chapter 3', 'KA655 Firmware'),
                        array(2, '3.1', 'Introduction'),
                        array(2, '3.2', 'KA655 Firmware Features'),
                        array(2, '3.3', 'Halt Entry, Exit, and Dispatch Code'),
                        array(2, '3.4', 'External Halts'),
                        array(2, '3.5', 'Power-Up Sequence'),
                        array(3, '3.5.1', 'Mode Switch Set to Test'),
                        array(3, '3.5.2', 'Mode Switch Set to Language Inquiry'),
                        array(3, '3.5.3', 'Mode Switch Set to Normal'),
                        array(2, '3.6', 'Bootstrap'),
                        array(3, '3.6.1', 'Bootstrap Initialization Sequence'),
                        array(3, '3.6.2', 'VMB Boot Flags'),
                        array(3, '3.6.3', 'Supported Boot Devices'),
                        array(3, '3.6.4', 'Autoboot'),
                        array(2, '3.7', 'Operating System Restart'),
                        array(3, '3.7.1', 'Restart Sequence'),
                        array(3, '3.7.2', 'Locating the RPB'),
                        array(2, '3.8', 'Console I/O Mode'),
                        array(3, '3.8.1', 'Command Syntax'),
                        array(3, '3.8.2', 'Address Specifiers'),
                        array(3, '3.8.3', 'Symbolic Addresses'),
                        array(3, '3.8.4', 'Console Command Qualifiers'),
                        array(3, '3.8.5', 'Console Command Keywords'),
                        array(2, '3.9', 'Console Commands'),
                        array(3, '3.9.1', 'BOOT'),
                        array(3, '3.9.2', 'CONFIGURE'),
                        array(3, '3.9.3', 'CONTINUE'),
                        array(3, '3.9.4', 'DEPOSIT'),
                        array(3, '3.9.5', 'EXAMINE'),
                        array(3, '3.9.6', 'FIND'),
                        array(3, '3.9.7', 'HALT'),
                        array(3, '3.9.8', 'HELP'),
                        array(3, '3.9.9', 'INITIALIZE'),
                        array(3, '3.9.10', 'MOVE'),
                        array(3, '3.9.11', 'NEXT'),
                        array(3, '3.9.12', 'REPEAT'),
                        array(3, '3.9.13', 'SEARCH'),
                        array(3, '3.9.14', 'SET'),
                        array(3, '3.9.15', 'SHOW'),
                        array(3, '3.9.16', 'START'),
                        array(3, '3.9.17', 'TEST'),
                        array(3, '3.9.18', 'UNJAM'),
                        array(3, '3.9.19', 'X---Binary Load and Unload'),
                        array(3, '3.9.20', '! (Comment)'),
                        array(1, 'Chapter 4', 'Troubleshooting and Diagnostics'),
                        array(2, '4.1', 'Introduction'),
                        array(2, '4.2', 'General Procedures'),
                        array(2, '4.3', 'KA655 ROM-Based Diagnostics'),
                        array(3, '4.3.1', 'Diagnostic Tests'),
                        array(3, '4.3.2', 'Scripts'),
                        array(3, '4.3.3', 'Script Calling Sequence'),
                        array(3, '4.3.4', 'Creating Scripts'),
                        array(3, '4.3.5', 'Console Displays'),
                        array(3, '4.3.6', 'System Halt Messages'),
                        array(3, '4.3.7', 'Console Error Messages'),
                        array(3, '4.3.8', 'VMB Error Messages'),
                        array(2, '4.4', 'Acceptance Testing'),
                        array(2, '4.5', 'Troubleshooting'),
                        array(3, '4.5.1', 'FE Utility'),
                        array(3, '4.5.2', 'Isolating Memory Failures'),
                        array(3, '4.5.3', 'Additional Troubleshooting Suggestions'),
                        array(2, '4.6', 'Loopback Tests'),
                        array(3, '4.6.1', 'Testing the Console Port'),
                        array(2, '4.7', 'Module Self-Tests'),
                        array(2, '4.8', 'RF-Series ISE Troubleshooting and Diagnostics'),
                        array(3, '4.8.1', 'DRVTST'),
                        array(3, '4.8.2', 'DRVEXR'),
                        array(3, '4.8.3', 'HISTRY'),
                        array(3, '4.8.4', 'ERASE'),
                        array(3, '4.8.5', 'PARAMS'),
                        array(4, '4.8.5.1', 'EXIT'),
                        array(4, '4.8.5.2', 'HELP'),
                        array(4, '4.8.5.3', 'SET'),
                        array(4, '4.8.5.4', 'SHOW'),
                        array(4, '4.8.5.5', 'STATUS'),
                        array(4, '4.8.5.6', 'WRITE'),
                        array(2, '4.9', 'Diagnostic Error Codes'),
                        array(1, 'Appendix A', 'Configuring the KFQSA'),
                        array(2, 'A.1', 'KFQSA Overview'),
                        array(3, 'A.1.1', 'Dual-Host Configuration'),
                        array(2, 'A.2', 'Configuring the KFQSA at Installation'),
                        array(3, 'A.2.1', 'Entering Console I/O Mode'),
                        array(3, 'A.2.2', 'Displaying Current Addresses'),
                        array(3, 'A.2.3', 'Running the Configure Utility'),
                        array(2, 'A.3', 'Programming the KFQSA'),
                        array(2, 'A.4', 'Reprogramming the KFQSA'),
                        array(2, 'A.5', 'Changing the ISE Allocation Class and Unit Number'),
                        array(1, 'Appendix B', 'KA655 CPU Address Assignments'),
                        array(2, 'B.1', 'General Local Address Space Map'),
                        array(2, 'B.2', 'Detailed Local Address Space Map'),
                        array(2, 'B.3', 'Internal Processor Registers'),
                        array(3, 'B.3.1', 'KA655 VAX Standard IPRs'),
                        array(3, 'B.3.2', 'KA655 Unique IPRs'),
                        array(2, 'B.4', 'Global Q22-Bus Address Space Map'),
                        array(1, 'Appendix C', 'Related Documentation')
                    )
                )
            );

        $this->_page->renderTableOfContents($pubId, true);

        $this->expectOutputString("<h2>Table of Contents</h2>\n"
            . "<div class=\"toc\">\n"
            . "<ul>\n"
            . "<li class=\"level1\"><span class=\"level1\">Chapter 1</span> KA655 CPU and Memory Subsystem\n"
            . "<ul>\n"
            . "<li class=\"level2\"><span>1.1</span> Introduction</li>\n"
            . "<li class=\"level2\"><span>1.2</span> KA655 CPU Features\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>1.2.1</span> Central Processing Unit (CPU)</li>\n"
            . "<li class=\"level3\"><span>1.2.2</span> Clock Functions</li>\n"
            . "<li class=\"level3\"><span>1.2.3</span> Floating Point Accelerator</li>\n"
            . "<li class=\"level3\"><span>1.2.4</span> Cache Memory</li>\n"
            . "<li class=\"level3\"><span>1.2.5</span> Memory Controller</li>\n"
            . "<li class=\"level3\"><span>1.2.6</span> MicroVAX System Support Functions</li>\n"
            . "<li class=\"level3\"><span>1.2.7</span> Resident Firmware</li>\n"
            . "<li class=\"level3\"><span>1.2.8</span> Q22-Bus Interface</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>1.3</span> KA655 Connectors</li>\n"
            . "<li class=\"level2\"><span>1.4</span> H3600-SA CPU I/O Panel</li>\n"
            . "<li class=\"level2\"><span>1.5</span> MS650-BA Memory</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level1\"><span class=\"level1\">Chapter 2</span> Configuration\n"
            . "<ul>\n"
            . "<li class=\"level2\"><span>2.1</span> Introduction</li>\n"
            . "<li class=\"level2\"><span>2.2</span> General Module Order\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>2.2.1</span> Module Order Rules for KA655 Systems</li>\n"
            . "<li class=\"level3\"><span>2.2.2</span> Recommended Module Order for KA655 Systems</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>2.3</span> Module Configuration</li>\n"
            . "<li class=\"level2\"><span>2.4</span> DSSI Configuration\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>2.4.1</span> Changing RF-Series ISE Parameters</li>\n"
            . "<li class=\"level3\"><span>2.4.2</span> Changing the Unit Number</li>\n"
            . "<li class=\"level3\"><span>2.4.3</span> Changing the Allocation Class</li>\n"
            . "<li class=\"level3\"><span>2.4.4</span> DSSI Cabling\n"
            . "<ul>\n"
            . "<li class=\"level4\"><span>2.4.4.1</span> DSSI Bus Termination and Length</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level3\"><span>2.4.5</span> Dual-Host Capability</li>\n"
            . "<li class=\"level3\"><span>2.4.6</span> Limitations to Dual-Host Configurations</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>2.5</span> Configuration Worksheet</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level1\"><span class=\"level1\">Chapter 3</span> KA655 Firmware\n"
            . "<ul>\n"
            . "<li class=\"level2\"><span>3.1</span> Introduction</li>\n"
            . "<li class=\"level2\"><span>3.2</span> KA655 Firmware Features</li>\n"
            . "<li class=\"level2\"><span>3.3</span> Halt Entry, Exit, and Dispatch Code</li>\n"
            . "<li class=\"level2\"><span>3.4</span> External Halts</li>\n"
            . "<li class=\"level2\"><span>3.5</span> Power-Up Sequence\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>3.5.1</span> Mode Switch Set to Test</li>\n"
            . "<li class=\"level3\"><span>3.5.2</span> Mode Switch Set to Language Inquiry</li>\n"
            . "<li class=\"level3\"><span>3.5.3</span> Mode Switch Set to Normal</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>3.6</span> Bootstrap\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>3.6.1</span> Bootstrap Initialization Sequence</li>\n"
            . "<li class=\"level3\"><span>3.6.2</span> VMB Boot Flags</li>\n"
            . "<li class=\"level3\"><span>3.6.3</span> Supported Boot Devices</li>\n"
            . "<li class=\"level3\"><span>3.6.4</span> Autoboot</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>3.7</span> Operating System Restart\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>3.7.1</span> Restart Sequence</li>\n"
            . "<li class=\"level3\"><span>3.7.2</span> Locating the RPB</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>3.8</span> Console I/O Mode\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>3.8.1</span> Command Syntax</li>\n"
            . "<li class=\"level3\"><span>3.8.2</span> Address Specifiers</li>\n"
            . "<li class=\"level3\"><span>3.8.3</span> Symbolic Addresses</li>\n"
            . "<li class=\"level3\"><span>3.8.4</span> Console Command Qualifiers</li>\n"
            . "<li class=\"level3\"><span>3.8.5</span> Console Command Keywords</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>3.9</span> Console Commands\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>3.9.1</span> BOOT</li>\n"
            . "<li class=\"level3\"><span>3.9.2</span> CONFIGURE</li>\n"
            . "<li class=\"level3\"><span>3.9.3</span> CONTINUE</li>\n"
            . "<li class=\"level3\"><span>3.9.4</span> DEPOSIT</li>\n"
            . "<li class=\"level3\"><span>3.9.5</span> EXAMINE</li>\n"
            . "<li class=\"level3\"><span>3.9.6</span> FIND</li>\n"
            . "<li class=\"level3\"><span>3.9.7</span> HALT</li>\n"
            . "<li class=\"level3\"><span>3.9.8</span> HELP</li>\n"
            . "<li class=\"level3\"><span>3.9.9</span> INITIALIZE</li>\n"
            . "<li class=\"level3\"><span>3.9.10</span> MOVE</li>\n"
            . "<li class=\"level3\"><span>3.9.11</span> NEXT</li>\n"
            . "<li class=\"level3\"><span>3.9.12</span> REPEAT</li>\n"
            . "<li class=\"level3\"><span>3.9.13</span> SEARCH</li>\n"
            . "<li class=\"level3\"><span>3.9.14</span> SET</li>\n"
            . "<li class=\"level3\"><span>3.9.15</span> SHOW</li>\n"
            . "<li class=\"level3\"><span>3.9.16</span> START</li>\n"
            . "<li class=\"level3\"><span>3.9.17</span> TEST</li>\n"
            . "<li class=\"level3\"><span>3.9.18</span> UNJAM</li>\n"
            . "<li class=\"level3\"><span>3.9.19</span> X---Binary Load and Unload</li>\n"
            . "<li class=\"level3\"><span>3.9.20</span> ! (Comment)</li>\n"
            . "</ul></li>\n"
            . "</ul></li>\n"
            . "<li class=\"level1\"><span class=\"level1\">Chapter 4</span> Troubleshooting and Diagnostics\n"
            . "<ul>\n"
            . "<li class=\"level2\"><span>4.1</span> Introduction</li>\n"
            . "<li class=\"level2\"><span>4.2</span> General Procedures</li>\n"
            . "<li class=\"level2\"><span>4.3</span> KA655 ROM-Based Diagnostics\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>4.3.1</span> Diagnostic Tests</li>\n"
            . "<li class=\"level3\"><span>4.3.2</span> Scripts</li>\n"
            . "<li class=\"level3\"><span>4.3.3</span> Script Calling Sequence</li>\n"
            . "<li class=\"level3\"><span>4.3.4</span> Creating Scripts</li>\n"
            . "<li class=\"level3\"><span>4.3.5</span> Console Displays</li>\n"
            . "<li class=\"level3\"><span>4.3.6</span> System Halt Messages</li>\n"
            . "<li class=\"level3\"><span>4.3.7</span> Console Error Messages</li>\n"
            . "<li class=\"level3\"><span>4.3.8</span> VMB Error Messages</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>4.4</span> Acceptance Testing</li>\n"
            . "<li class=\"level2\"><span>4.5</span> Troubleshooting\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>4.5.1</span> FE Utility</li>\n"
            . "<li class=\"level3\"><span>4.5.2</span> Isolating Memory Failures</li>\n"
            . "<li class=\"level3\"><span>4.5.3</span> Additional Troubleshooting Suggestions</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>4.6</span> Loopback Tests\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>4.6.1</span> Testing the Console Port</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>4.7</span> Module Self-Tests</li>\n"
            . "<li class=\"level2\"><span>4.8</span> RF-Series ISE Troubleshooting and Diagnostics\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>4.8.1</span> DRVTST</li>\n"
            . "<li class=\"level3\"><span>4.8.2</span> DRVEXR</li>\n"
            . "<li class=\"level3\"><span>4.8.3</span> HISTRY</li>\n"
            . "<li class=\"level3\"><span>4.8.4</span> ERASE</li>\n"
            . "<li class=\"level3\"><span>4.8.5</span> PARAMS\n"
            . "<ul>\n"
            . "<li class=\"level4\"><span>4.8.5.1</span> EXIT</li>\n"
            . "<li class=\"level4\"><span>4.8.5.2</span> HELP</li>\n"
            . "<li class=\"level4\"><span>4.8.5.3</span> SET</li>\n"
            . "<li class=\"level4\"><span>4.8.5.4</span> SHOW</li>\n"
            . "<li class=\"level4\"><span>4.8.5.5</span> STATUS</li>\n"
            . "<li class=\"level4\"><span>4.8.5.6</span> WRITE</li>\n"
            . "</ul></li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>4.9</span> Diagnostic Error Codes</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level1\"><span class=\"level1\">Appendix A</span> Configuring the KFQSA\n"
            . "<ul>\n"
            . "<li class=\"level2\"><span>A.1</span> KFQSA Overview\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>A.1.1</span> Dual-Host Configuration</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>A.2</span> Configuring the KFQSA at Installation\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>A.2.1</span> Entering Console I/O Mode</li>\n"
            . "<li class=\"level3\"><span>A.2.2</span> Displaying Current Addresses</li>\n"
            . "<li class=\"level3\"><span>A.2.3</span> Running the Configure Utility</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>A.3</span> Programming the KFQSA</li>\n"
            . "<li class=\"level2\"><span>A.4</span> Reprogramming the KFQSA</li>\n"
            . "<li class=\"level2\"><span>A.5</span> Changing the ISE Allocation Class and Unit Number</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level1\"><span class=\"level1\">Appendix B</span> KA655 CPU Address Assignments\n"
            . "<ul>\n"
            . "<li class=\"level2\"><span>B.1</span> General Local Address Space Map</li>\n"
            . "<li class=\"level2\"><span>B.2</span> Detailed Local Address Space Map</li>\n"
            . "<li class=\"level2\"><span>B.3</span> Internal Processor Registers\n"
            . "<ul>\n"
            . "<li class=\"level3\"><span>B.3.1</span> KA655 VAX Standard IPRs</li>\n"
            . "<li class=\"level3\"><span>B.3.2</span> KA655 Unique IPRs</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level2\"><span>B.4</span> Global Q22-Bus Address Space Map</li>\n"
            . "</ul></li>\n"
            . "<li class=\"level1\"><span class=\"level1\">Appendix C</span> Related Documentation</li>\n"
            . "</ul></div>");
    }

    public function testRenderCopiesNoAmendment()
    {
        $pubId = 123;
        $this->_db->expects($this->once())->method('getCopiesForPub')
            ->with($pubId)
            ->willReturn(DatabaseTester::createResultRowsForColumns(
                array('format', 'url', 'notes', 'size', 'name', 'site_url', 'description', 'copy_base', 'low', 'md5', 'amend_serial', 'credits', 'copy_id'),
                array(
                    array('PDF', 'http://vt100.net/mirror/hcps/306aamg1.pdf', NULL, 49351262, 'VT100.net', 'http://vt100.net/', "Paul Williams' VT100.net", 'http://vt100.net/', 'N', NULL, NULL, NULL, 7165),
                    array('PDF', 'http://bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf', 'Missing page 4-49', 12023683, 'bitsavers', 'http://bitsavers.org/', "Al Kossow's Bitsavers", 'http://bitsavers.org/pdf/', 'N', '15a565c18a743c558203f776ee3d6d87', NULL, NULL, 9214)
                    ))
            );
        $this->_db->expects($this->exactly(2))->method('getMirrorsForCopy')
            ->withConsecutive([ 7165 ], [ 9214 ])
            ->willReturn(
                array(),
                array('http://bitsavers.trailing-edge.com/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
                    'http://www.bighole.nl/pub/mirror/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
                    'http://www.textfiles.com/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
                    'http://computer-refuge.org/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf',
                    'http://www.mirrorservice.org/sites/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf')
            );

        $this->_page->renderCopies($pubId);

        $this->expectOutputString(
            "<h2>Copies</h2>\n"
            . "<table>\n"
            . "<tbody><tr>\n"
            . "<td>Address:</td>\n"
            . "<td><a href=\"http://vt100.net/mirror/hcps/306aamg1.pdf\">http://vt100.net/mirror/hcps/306aamg1.pdf</a></td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Site:</td>\n"
            . "<td><a href=\"http://vt100.net/\">Paul Williams' VT100.net</a></td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Format:</td>\n"
            . "<td>PDF</td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Size:</td>\n"
            . "<td>49351262 bytes (47.1 MiB)</td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td colspan=\"2\">&nbsp;</td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Address:</td>\n"
            . "<td><a href=\"http://bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf\">http://bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf</a></td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Site:</td>\n"
            . "<td><a href=\"http://bitsavers.org/\">Al Kossow's Bitsavers</a></td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Format:</td>\n"
            . "<td>PDF</td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Size:</td>\n"
            . "<td>12023683 bytes (11.5 MiB)</td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>MD5:</td>\n"
            . "<td>15a565c18a743c558203f776ee3d6d87</td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Notes:</td>\n"
            . "<td>Missing page 4-49</td>\n"
            . "</tr>\n"
            . "<tr valign=\"top\"><td>Mirrors:</td>"
            . "<td><ul style=\"list-style-type: none; margin: 0; padding: 0\">"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://bitsavers.trailing-edge.com/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf\">http://bitsavers.trailing-edge.com/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf</a></li>"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://www.bighole.nl/pub/mirror/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf\">http://www.bighole.nl/pub/mirror/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf</a></li>"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://www.textfiles.com/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf\">http://www.textfiles.com/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf</a></li>"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://computer-refuge.org/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf\">http://computer-refuge.org/bitsavers/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf</a></li>"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://www.mirrorservice.org/sites/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf\">http://www.mirrorservice.org/sites/www.bitsavers.org/pdf/dec/vax/655/EK-306A-MG-001_655Mnt_Mar89.pdf</a></li></ul></td></tr>"
            . "</tbody>\n"
            . "</table>\n");
    }

    public function testRenderSupersessionPubSupersedesOlder()
    {
        $pubId = 6105;
        $this->_db->expects($this->once())->method('getPublicationsSupersededByPub')
            ->with($pubId)
            ->willReturn(array(array('ph_company' => 1, 'ph_pub' => 23, 'ph_part' => 'EK-11024-TM-PRE', 'ph_title' => 'PDP-11/24 System Technical Manual')));
        $this->_db->expects($this->once())->method('getPublicationsSupersedingPub')
            ->with($pubId)
            ->willReturn(array());

        $this->_page->renderSupersessions($pubId);

        $this->expectOutputString('<tr valign="top"><td>Supersedes:</td>'
            . '<td><ul class="citelist">'
            . '<li>EK-11024-TM-PRE, <a href="../details.php/1,23"><cite>PDP-11/24 System Technical Manual</cite></a></li>'
            . "</ul></td></tr>\n");
    }

    public function testRenderSupersessionPubSupersededByNewer()
    {
        $pubId = 23;
        $this->_db->expects($this->once())->method('getPublicationsSupersedingPub')
            ->with($pubId)
            ->willReturn(array(array('ph_company' => 1, 'ph_pub' => 6105, 'ph_part' => 'EK-11024-TM-001', 'ph_title' => 'PDP-11/24 System Technical Manual')));
        $this->_db->expects($this->once())->method('getPublicationsSupersededByPub')
            ->with($pubId)
            ->willReturn(array());

        $this->_page->renderSupersessions($pubId);

        $this->expectOutputString('<tr valign="top"><td>Superseded by:</td>'
            . '<td><ul class="citelist">'
            . '<li>EK-11024-TM-001, <a href="../details.php/1,6105"><cite>PDP-11/24 System Technical Manual</cite></a></li>'
            . "</ul></td></tr>\n");
    }

    public function testRenderCopiesAmended()
    {
        $pubId = 123;
        $copyId = 10277;
        $this->_db->expects($this->once())->method('getCopiesForPub')
            ->with($pubId)
            ->willReturn(
                DatabaseTester::createResultRowsForColumns(
                array('format', 'url', 'notes', 'size', 'name', 'site_url', 'description', 'copy_base', 'low', 'md5', 'amend_serial', 'credits', 'copy_id'),
                array(
                    array('PDF', 'http://bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf', NULL, 25939827, 'bitsavers', 'http://bitsavers.org/', "Al Kossow's Bitsavers", 'http://bitsavers.org/pdf/', 'N', '0f91ba7f8d99ce7a9b57f9fdb07d3561', 7, NULL, $copyId)
            )));
        $this->_db->expects($this->once())->method('getMirrorsForCopy')
            ->with($copyId)
            ->willReturn(array('http://bitsavers.trailing-edge.com/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf',
                'http://www.bighole.nl/pub/mirror/www.bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf',
                'http://www.textfiles.com/bitsavers/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf',
                'http://computer-refuge.org/bitsavers/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf',
                'http://www.mirrorservice.org/sites/www.bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf'));
        $this->_db->expects($this->once())->method('getAmendedPub')
            ->with($pubId)
            ->willReturn(array('ph_company' => 57, 'pub_id' => 17971, 'ph_part' => 'AB81-14G',
                'ph_title' => 'Honeywell Publications Catalog Addendum G', 'ph_pub_date' => '1984-02'));

        $this->_page->renderCopies($pubId);

        $this->expectOutputString(
            "<h2>Copies</h2>\n"
            . "<table>\n"
            . "<tbody><tr>\n"
            . "<td>Address:</td>\n"
            . "<td><a href=\"http://bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf\">http://bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf</a></td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Site:</td>\n"
            . "<td><a href=\"http://bitsavers.org/\">Al Kossow's Bitsavers</a></td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Format:</td>\n"
            . "<td>PDF</td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Size:</td>\n"
            . "<td>25939827 bytes (24.7 MiB)</td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>MD5:</td>\n"
            . "<td>0f91ba7f8d99ce7a9b57f9fdb07d3561</td>\n"
            . "</tr>\n"
            . "<tr>\n"
            . "<td>Amended to:</td>\n"
            . "<td>AB81-14G, <a href=\"../details.php/57,17971\"><cite>Honeywell Publications Catalog Addendum G</cite></a> (1984-02)</td>\n"
            . "</tr>\n"
            . "<tr valign=\"top\"><td>Mirrors:</td>"
            . "<td><ul style=\"list-style-type: none; margin: 0; padding: 0\">"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://bitsavers.trailing-edge.com/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf\">http://bitsavers.trailing-edge.com/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf</a></li>"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://www.bighole.nl/pub/mirror/www.bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf\">http://www.bighole.nl/pub/mirror/www.bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf</a></li>"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://www.textfiles.com/bitsavers/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf\">http://www.textfiles.com/bitsavers/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf</a></li>"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://computer-refuge.org/bitsavers/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf\">http://computer-refuge.org/bitsavers/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf</a></li>"
            . "<li style=\"margin: 0; padding: 0\"><a href=\"http://www.mirrorservice.org/sites/www.bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf\">http://www.mirrorservice.org/sites/www.bitsavers.org/pdf/honeywell/AB81-14_PubsCatalog_May83.pdf</a></li>"
            . "</ul></td></tr></tbody>\n"
            . "</table>\n");
    }

    public function testRenderDetails()
    {
        $_SERVER['PATH_INFO'] = '/1,3';
        $pubId = 3;
        $rows = DatabaseTester::createResultRowsForColumns(
            array('pub_id', 'name', 'ph_part', 'ph_pub_date', 'ph_title', 'ph_abstract',
                'ph_revision', 'ph_ocr_file', 'ph_cover_image', 'ph_lang', 'ph_keywords'),
            array(array($pubId, 'Digital Equipment Corporation', 'AA-K336A-TK', NULL, 'GIGI/ReGIS Handbook', NULL,
                '', NULL, 'gigi_regis_handbook.png', '+en', 'VK100')));
        $this->_db->expects($this->once())->method('getDetailsForPub')
            ->with($pubId)
            ->willReturn($rows[0]);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $this->_config = $config;
        $page = new RenderDetailsTester($this->_config);

        $page->renderBodyContent();

        $this->assertTrue($page->renderLanguageCalled);
        $this->assertEquals('+en', $page->renderLanguageLastLanguage);
        $this->assertTrue($page->renderAmendmentsCalled);
        $this->assertEquals(3, $page->renderAmendmentsLastPubId);
        $this->assertTrue($page->renderOSTagsCalled);
        $this->assertEquals(3, $page->renderOSTagsLastPubId);
        $this->assertTrue($page->renderLongDescriptionCalled);
        $this->assertEquals(3, $page->renderLongDescriptionLastPubId);
        $this->assertTrue($page->renderCitationsCalled);
        $this->assertEquals(3, $page->renderCitationsLastPubId);
        $this->assertTrue($page->renderSupersessionsCalled);
        $this->assertEquals(3, $page->renderSupersessionsLastPubId);
        $this->assertTrue($page->renderTableOfContentsCalled);
        $this->assertEquals(3, $page->renderTableOfContentsLastPubId);
        $this->assertEquals(true, $page->renderTableOfContentsLastFullContents);
        $this->assertTrue($page->renderCopiesCalled);
        $this->assertEquals(3, $page->renderCopiesLastPubId);
        $expected = implode("\n", array(
            '<div style="float:right; margin: 10px"><img src="gigi_regis_handbook.png" alt="" /></div><div class="det"><h1>GIGI/ReGIS Handbook</h1>',
            '<table><tbody><tr><td>Company:</td><td><a href="../search.php?cp=1&q=">Digital Equipment Corporation</a></td></tr>',
            '<tr><td>Part:</td><td>AA-K336A-TK</td></tr>',
            '<tr><td>Date:</td><td></td></tr>',
            '<tr><td>Keywords:</td><td>VK100</td></tr>',
            '</tbody>',
            '</table>',
            '</div>')) . "\n";
        $this->expectOutputString($expected);
    }
}
