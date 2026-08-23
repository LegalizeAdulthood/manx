<?php

require_once __DIR__ . '/../vendor/autoload.php';

// For SORT_ORDER_xxx
require_once __DIR__ . '/../public/pages/UnknownPathDefs.php';

use Pimple\Container;

class WhatsNewPageTester extends Manx\WhatsNewPage
{
    public $headers = [];

    protected function sendHeader($field)
    {
        $this->headers[] = $field;
    }

    public function renderHeader()
    {
        parent::renderHeader();
    }

    // lift visibility of some functions for testing
    public function renderBodyContent()
    {
        parent::renderBodyContent();
    }

    public function ignorePaths()
    {
        parent::ignorePaths();
    }

    public function savePartRegex()
    {
        parent::savePartRegex();
    }

    public function ingestPreviewRows()
    {
        parent::ingestPreviewRows();
    }

    public function previewRows($thisDir, $files)
    {
        return parent::previewRows($thisDir, $files);
    }

    public function getTitle()
    {
        return parent::getTitle();
    }
}

class WhatsNewPageTest extends Manx\Test\TestCase
{
    /** @var Container */
    private $_config;

    /** @var Manx\IManxDatabase */
    private $_db;
    /** @var Manx\IManx */
    private $_manx;
    /** @var Manx\IFileSystem */
    private $_fileSystem;
    /** @var Manx\IWhatsNewPageFactory */
    private $_factory;
    /** @var Manx\IUrlInfo */
    private $_info;
    /** @var Manx\IUrlTransfer */
    private $_transfer;
    /** @var WhatsNewPageTester */
    private $_page;
    /** @var Manx\IWhatsNewIndex */
    private $_whatsNewIndex;

    private function createPage($vars = array('sort' => SORT_ORDER_BY_ID))
    {
        $_SERVER['PATH_INFO'] = '';
        $this->_config['vars'] = $vars;
        $this->_page = new WhatsNewPageTester($this->_config);
    }

    protected function setUp(): void
    {
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_manx = $this->createMock(Manx\IManx::class);
        $this->_manx->method('getDatabase')->willReturn($this->_db);
        $this->_fileSystem = $this->createMock(Manx\IFileSystem::class);
        $this->_factory = $this->createMock(Manx\IWhatsNewPageFactory::class);
        $this->_info = $this->createMock(Manx\IUrlInfo::class);
        $this->_transfer = $this->createMock(Manx\IUrlTransfer::class);
        $this->_whatsNewIndex = $this->createMock(Manx\IWhatsNewIndex::class);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $config['fileSystem'] = $this->_fileSystem;
        $config['whatsNewIndex'] = $this->_whatsNewIndex;
        $config['whatsNewPageFactory'] = $this->_factory;
        Manx\BitSaversConfig::configure($config);
        $this->_config = $config;
    }

    public function testConstruct()
    {
        $this->_db->expects($this->never())->method('getProperty')->with('bitsavers_whats_new_timestamp');
        $this->_factory->expects($this->never())->method('createUrlTransfer');
        $this->_db->expects($this->never())->method('addSiteUnknownPaths');

        $this->createPage();

        $this->assertTrue(is_object($this->_page));
        $this->assertFalse(is_null($this->_page));
    }

    public function testRenderHeaderPreventsBrowserCaching()
    {
        $this->createPage(['parentDir' => -1]);

        ob_start();
        $this->_page->renderHeader();
        ob_end_clean();

        $this->assertContains("Cache-Control: no-store, no-cache, must-revalidate, max-age=0",
            $this->_page->headers);
        $this->assertContains("Pragma: no-cache", $this->_page->headers);
        $this->assertContains("Expires: 0", $this->_page->headers);
    }

    public function testTitleForRoot()
    {
        $this->createPage(['parentDir' => -1]);
        $this->_db->expects($this->never())->method('getSiteUnknownDir');

        $this->assertEquals('BitSavers', $this->_page->getTitle());
    }

    public function testTitleForDir()
    {
        $parentDirId = 1339;
        $this->createPage(['parentDir' => $parentDirId]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex'],
            [
                [100, 3, 'dec/pdp11', 150, '']
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')->with($parentDirId)->willReturn($thisDirRows[0]);

        $this->assertEquals('BitSavers dec/pdp11', $this->_page->getTitle());
    }

    public function testRenderBodyContentNoRootPaths()
    {
        $siteName = 'bitsavers';
        $parentDirId = -1;
        $this->createPage(['siteName' => $siteName, 'parentDir' => $parentDirId]);
        $this->_db->expects($this->never())->method('getSiteUnknownDir');
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')->with($siteName, $parentDirId)->willReturn([]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')->with($siteName, $parentDirId)->willReturn([]);

        $this->_page->renderBodyContent();

        $this->expectOutputStringIgnoringLineEndings("<h1>No New BitSavers Publications Found</h1>\n");
    }

    public function testRenderBodyContentNoDocumentsForDir()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $this->createPage(['siteName' => $siteName, 'parentDir' => $parentDirId]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex'],
            [
                [100, 3, 'dec/pdp11', 150, '']
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')->with($parentDirId)->willReturn($thisDirRows[0]);
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')->with($siteName, $parentDirId)->willReturn([]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')->with($siteName, $parentDirId)->willReturn([]);

        $this->_page->renderBodyContent();

        $expected = <<<EOH
<h1>No New BitSavers dec/pdp11 Publications Found</h1>

<form action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="bitsavers" />
<input type="hidden" name="parentDir" value="1339" />
<fieldset>
<legend>Directory Metadata</legend>
<label for="part_regex">Part Regex</label>
<input type="text" id="part_regex" name="part_regex" size="60" value="" />
<input type="submit" value="Save" />
</fieldset>
</form>

<ul>
<li><a href="whatsnew.php?site=bitsavers&parentDir=150#D100">(parent)</a></li>
</ul>

EOH;
        $this->expectOutputStringIgnoringLineEndings($expected);
    }

    public function testRenderBodyContent()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $this->createPage(['siteName' => $siteName, 'parentDir' => $parentDirId]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/pdp11', 150,
                    Manx\UrlMetaData::DEFAULT_PART_REGEX, 0]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')->with($parentDirId)->willReturn($thisDirRows[0]);
        $dirRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [111, 3, 'dec/pdp11/1103', 1339, '', 0],
                [112, 3, 'dec/pdp11/1104', 1339, '', 0],
                [113, 3, 'dec/pdp11/1105', 1339, '', 0],
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')
            ->with($siteName, $parentDirId)
            ->willReturn($dirRows);
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3, 'KM11_Maintenance_Panel_May70.pdf', 0, 0, 1339],
                [223, 3, 'EK0LSIFS-SV-005_LSI-11_Systems_Service_Manual_Volume_3_Jan85.pdf', 0, 0, 1339],
                [224, 3, 'LSI-11_Systems_Service_Manual_Aug81.pdf', 0, 0, 1339],
                [225, 3, 'firmware.zip', 1, 0, 1339]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)
            ->willReturn($fileRows);
        $this->_db->expects($this->exactly(3))->method('getFormatForExtension')
            ->withConsecutive(['pdf'], ['pdf'], ['pdf'])
            ->willReturn('PDF', 'PDF', 'PDF');

        $this->_page->renderBodyContent();

        $expected = <<<EOH
<h1>New BitSavers dec/pdp11 Publications</h1>

<form action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="bitsavers" />
<input type="hidden" name="parentDir" value="1339" />
<fieldset>
<legend>Directory Metadata</legend>
<label for="part_regex">Part Regex</label>
<input type="text" id="part_regex" name="part_regex" size="60" value="^([^_]*[0-9][0-9][^_]*)_" />
<input type="submit" value="Save" />
</fieldset>
</form>

<ul>
<li><a href="whatsnew.php?site=bitsavers&parentDir=150#D100">(parent)</a></li>
<li><span id="D111"><a href="whatsnew.php?site=bitsavers&parentDir=111">dec/pdp11/1103</a></span></li>
<li><span id="D112"><a href="whatsnew.php?site=bitsavers&parentDir=112">dec/pdp11/1104</a></span></li>
<li><span id="D113"><a href="whatsnew.php?site=bitsavers&parentDir=113">dec/pdp11/1105</a></span></li>
</ul>
<form action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="bitsavers" />
<input type="hidden" name="parentDir" value="1339" />
<table>
<tr><th>Ignored?</th><th>File</th></tr>
<tr><td><input type="checkbox" id="ignore0" name="ignore0" value="222"/></td>
<td><a href="url-wizard.php?id=222&amp;url=http%3A%2F%2Fbitsavers.org%2Fpdf%2Fdec%2Fpdp11%2FKM11_Maintenance_Panel_May70.pdf">KM11_Maintenance_Panel_May70.pdf</a></td></tr>
<tr><td><input type="checkbox" id="ignore1" name="ignore1" value="223"/></td>
<td><a href="url-wizard.php?id=223&amp;url=http%3A%2F%2Fbitsavers.org%2Fpdf%2Fdec%2Fpdp11%2FEK0LSIFS-SV-005_LSI-11_Systems_Service_Manual_Volume_3_Jan85.pdf">EK0LSIFS-SV-005_LSI-11_Systems_Service_Manual_Volume_3_Jan85.pdf</a></td></tr>
<tr><td><input type="checkbox" id="ignore2" name="ignore2" value="224"/></td>
<td><a href="url-wizard.php?id=224&amp;url=http%3A%2F%2Fbitsavers.org%2Fpdf%2Fdec%2Fpdp11%2FLSI-11_Systems_Service_Manual_Aug81.pdf">LSI-11_Systems_Service_Manual_Aug81.pdf</a></td></tr>
<tr><td><input type="checkbox" id="ignore3" name="ignore3" value="225" checked/></td>
<td><a href="url-wizard.php?id=225&amp;url=http%3A%2F%2Fbitsavers.org%2Fpdf%2Fdec%2Fpdp11%2Ffirmware.zip">firmware.zip</a></td></tr>
</table>
<input type="submit" value="Ignore" />
</form>

EOH;
        $this->expectOutputStringIgnoringLineEndings($expected);
    }

    public function testRenderBodyContentEscapesSpecialPathSegments()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $this->createPage(['siteName' => $siteName, 'parentDir' => $parentDirId]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/foo#bar', 150, '', 0]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')
            ->with($parentDirId)->willReturn($thisDirRows[0]);
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')
            ->with($siteName, $parentDirId)
            ->willReturn([]);
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3, 'EK-11#1 & Guide.pdf', 0, 0, 1339]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)
            ->willReturn($fileRows);
        $this->_db->expects($this->once())->method('getFormatForExtension')
            ->with('pdf')
            ->willReturn('PDF');

        $this->_page->renderBodyContent();

        $expected = <<<EOH
<h1>New BitSavers dec/foo#bar Publications</h1>

<form action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="bitsavers" />
<input type="hidden" name="parentDir" value="1339" />
<fieldset>
<legend>Directory Metadata</legend>
<label for="part_regex">Part Regex</label>
<input type="text" id="part_regex" name="part_regex" size="60" value="" />
<input type="submit" value="Save" />
</fieldset>
</form>

<ul>
<li><a href="whatsnew.php?site=bitsavers&parentDir=150#D100">(parent)</a></li>
</ul>
<form action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="bitsavers" />
<input type="hidden" name="parentDir" value="1339" />
<table>
<tr><th>Ignored?</th><th>File</th></tr>
<tr><td><input type="checkbox" id="ignore0" name="ignore0" value="222"/></td>
<td><a href="url-wizard.php?id=222&amp;url=http%3A%2F%2Fbitsavers.org%2Fpdf%2Fdec%2Ffoo%2523bar%2FEK-11%25231%2520%2526%2520Guide.pdf">EK-11#1 &amp; Guide.pdf</a></td></tr>
</table>
<input type="submit" value="Ignore" />
</form>

EOH;
        $this->expectOutputStringIgnoringLineEndings($expected);
    }

    public function testSavePartRegex()
    {
        $parentDirId = 1339;
        $partRegex = '^([^_]+)_';
        $this->createPage(['siteName' => 'bitsavers',
            'parentDir' => $parentDirId, 'part_regex' => $partRegex]);
        $this->_db->expects($this->once())
            ->method('updateSiteUnknownDirPartRegex')
            ->with($parentDirId, $partRegex);

        $this->_page->savePartRegex();
    }

    public function testClearPartRegex()
    {
        $parentDirId = 1339;
        $this->createPage(['siteName' => 'bitsavers',
            'parentDir' => $parentDirId, 'part_regex' => '']);
        $this->_db->expects($this->once())
            ->method('updateSiteUnknownDirPartRegex')
            ->with($parentDirId, '');

        $this->_page->savePartRegex();
    }

    public function testSavePartRegexRejectsInvalidRegex()
    {
        $parentDirId = 1339;
        $this->createPage(['siteName' => 'bitsavers',
            'parentDir' => $parentDirId, 'part_regex' => '([broken']);
        $this->_db->expects($this->never())
            ->method('updateSiteUnknownDirPartRegex');

        $this->_page->savePartRegex();
    }

    public function testInvalidPartRegexErrorIsRendered()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId, 'part_regex' => '([broken']);
        $this->_page->savePartRegex();
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/pdp11', 150, '', 0]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')
            ->with($parentDirId)->willReturn($thisDirRows[0]);
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')
            ->with($siteName, $parentDirId)->willReturn([]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)->willReturn([]);

        $this->_page->renderBodyContent();

        $this->expectOutputRegex('/Invalid part-number regex\\./');
    }

    public function testPreviewRowsExtractMetadataAndStatus()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $companyId = 13;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId]);
        $thisDir = [
            'id' => 100,
            'site_id' => 3,
            'path' => 'dec/pdp11',
            'parent_dir_id' => 150,
            'part_regex' => Manx\UrlMetaData::DEFAULT_PART_REGEX,
            'ignored' => 0
        ];
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId]
            ]);
        $url = 'http://bitsavers.org/pdf/dec/pdp11/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pubRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
            [
                [23, 'EK-3333-01', 'Jumbotron Users Guide', '1977-02']
            ]);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn($companyId);
        $this->_db->expects($this->once())->method('getFormatForExtension')
            ->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->once())->method('copyExistsForUrl')
            ->with($url)->willReturn(false);
        $this->_db->expects($this->once())
            ->method('getPublicationsForPartNumber')
            ->with('EK-3333-01', $companyId)->willReturn($pubRows);

        $rows = $this->_page->previewRows($thisDir, $fileRows);

        $this->assertEquals([
            [
                'id' => 222,
                'site_id' => 3,
                'pub_id' => 23,
                'path' => 'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                'url' => $url,
                'part' => 'EK-3333-01',
                'pub_date' => '1977-02',
                'title' => 'Jumbotron Users Guide',
                'format' => 'PDF',
                'regex_result' => 'Match',
                'matching_publication' =>
                    '<a href="details.php/13,23">Jumbotron Users Guide</a>',
                'existing_copy' => 'No',
                'status' => 'Accepted',
                'status_detail' => ''
            ]
        ], $rows);
    }

    public function testPreviewRowsAcceptSingleExactMatchFromMultipleResults()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $companyId = 13;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId]);
        $thisDir = [
            'id' => 100,
            'site_id' => 3,
            'path' => 'dec/pdp11',
            'parent_dir_id' => 150,
            'part_regex' => Manx\UrlMetaData::DEFAULT_PART_REGEX,
            'ignored' => 0
        ];
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId]
            ]);
        $url = 'http://bitsavers.org/pdf/dec/pdp11/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pubRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
            [
                [31, 'EK-3333', 'Jumbotron Overview', '1977-01'],
                [23, 'EK-3333-01', 'Jumbotron Users Guide', '1977-02']
            ]);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn($companyId);
        $this->_db->expects($this->once())->method('getFormatForExtension')
            ->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->once())->method('copyExistsForUrl')
            ->with($url)->willReturn(false);
        $this->_db->expects($this->once())
            ->method('getPublicationsForPartNumber')
            ->with('EK-3333-01', $companyId)->willReturn($pubRows);

        $rows = $this->_page->previewRows($thisDir, $fileRows);

        $this->assertEquals('Accepted', $rows[0]['status']);
        $this->assertEquals(23, $rows[0]['pub_id']);
        $this->assertEquals(
            '<a href="details.php/13,23">Jumbotron Users Guide</a>',
            $rows[0]['matching_publication']);
    }

    public function testPreviewRowsExcludeDefaultIgnoredFiles()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId]);
        $thisDir = [
            'id' => 100,
            'site_id' => 3,
            'path' => 'dec/pdp11',
            'parent_dir_id' => 150,
            'part_regex' => Manx\UrlMetaData::DEFAULT_PART_REGEX,
            'ignored' => 0
        ];
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [225, 3, 'firmware.zip', 0, 0, $parentDirId]
            ]);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn(13);
        $this->_db->expects($this->once())->method('getFormatForExtension')
            ->with('zip')->willReturn('');
        $this->_db->expects($this->never())->method('copyExistsForUrl');
        $this->_db->expects($this->never())
            ->method('getPublicationsForPartNumber');

        $rows = $this->_page->previewRows($thisDir, $fileRows);

        $this->assertEquals([], $rows);
    }

    public function testPreviewRowsSkipWithoutCompanyAssociation()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId]);
        $thisDir = [
            'id' => 100,
            'site_id' => 3,
            'path' => 'unknown/path',
            'parent_dir_id' => 150,
            'part_regex' => Manx\UrlMetaData::DEFAULT_PART_REGEX,
            'ignored' => 0
        ];
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId]
            ]);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'unknown/path')->willReturn(-1);
        $this->_db->expects($this->never())->method('getFormatForExtension');
        $this->_db->expects($this->never())->method('copyExistsForUrl');
        $this->_db->expects($this->never())
            ->method('getPublicationsForPartNumber');

        $rows = $this->_page->previewRows($thisDir, $fileRows);

        $this->assertEquals([], $rows);
    }

    public function testPreviewRowsClassifyDuplicateRejectedAndUncertain()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $companyId = 13;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId]);
        $thisDir = [
            'id' => 100,
            'site_id' => 3,
            'path' => 'dec/pdp11',
            'parent_dir_id' => 150,
            'part_regex' => Manx\UrlMetaData::DEFAULT_PART_REGEX,
            'ignored' => 0
        ];
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId],
                [223, 3, 'LSI-1_Systems_Service_Manual_Aug81.pdf',
                    0, 0, $parentDirId],
                [224, 3,
                    'EK-4444-01_Jumbotron_Reference_Manual_Feb1977.pdf',
                    0, 0, $parentDirId],
                [225, 3,
                    'EK-5555-01_New_System_Manual_Feb1977.pdf',
                    0, 0, $parentDirId]
            ]);
        $duplicateUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $rejectedUrl = 'http://bitsavers.org/pdf/dec/pdp11/LSI-1_Systems_Service_Manual_Aug81.pdf';
        $uncertainUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-4444-01_Jumbotron_Reference_Manual_Feb1977.pdf';
        $newUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-5555-01_New_System_Manual_Feb1977.pdf';
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn($companyId);
        $this->_db->expects($this->exactly(4))
            ->method('getFormatForExtension')
            ->withConsecutive(['pdf'], ['pdf'], ['pdf'], ['pdf'])
            ->willReturn('PDF', 'PDF', 'PDF', 'PDF');
        $this->_db->expects($this->exactly(4))->method('copyExistsForUrl')
            ->withConsecutive([$duplicateUrl], [$rejectedUrl], [$uncertainUrl],
                [$newUrl])
            ->willReturn(
                ['ph_company' => $companyId, 'ph_pub' => 23,
                    'ph_title' => 'Jumbotron Users Guide'],
                false,
                false,
                false);
        $this->_db->expects($this->exactly(2))
            ->method('getPublicationsForPartNumber')
            ->withConsecutive(['EK-4444-01', $companyId],
                ['EK-5555-01', $companyId])
            ->willReturn(
                \Manx\Test\RowFactory::createResultRowsForColumns(
                    ['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
                    [
                        [31, 'EK-4444-01', 'Jumbotron Reference Manual', '1977-02'],
                        [32, 'EK-4444-01', 'Jumbotron Pocket Guide', '1978-04']
                    ]),
                []);

        $rows = $this->_page->previewRows($thisDir, $fileRows);

        $this->assertEquals(
            ['Duplicate', 'Rejected', 'Uncertain', 'New'],
            array_column($rows, 'status'));
        $this->assertEquals(
            [
                'A copy already exists for this URL: Jumbotron Users Guide.',
                'The directory part-number regex did not match the filename.',
                'The extracted part number EK-4444-01 matched 2 publications.',
                'No publication matched the extracted part number EK-5555-01.'
            ],
            array_column($rows, 'status_detail'));
        $this->assertEquals(
            '<a href="details.php/13,23">Jumbotron Users Guide</a>',
            $rows[0]['existing_copy']);
        $this->assertEquals(
            '<a href="search.php?cp=13&amp;q=EK-4444-01">2 candidates</a>',
            $rows[2]['matching_publication']);
    }

    public function testRenderBodyContentPlacesPreviewBeforeLists()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $companyId = 13;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/pdp11', 150,
                    Manx\UrlMetaData::DEFAULT_PART_REGEX, 0]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')
            ->with($parentDirId)->willReturn($thisDirRows[0]);
        $dirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [111, 3, 'dec/pdp11/1103', 1339, '', 0]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')
            ->with($siteName, $parentDirId)->willReturn($dirRows);
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId]
            ]);
        $url = 'http://bitsavers.org/pdf/dec/pdp11/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $pubRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
            [
                [23, 'EK-3333-01', 'Jumbotron Users Guide', '1977-02']
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)->willReturn($fileRows);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn($companyId);
        $this->_db->expects($this->once())->method('getFormatForExtension')
            ->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->once())->method('copyExistsForUrl')
            ->with($url)->willReturn(false);
        $this->_db->expects($this->once())
            ->method('getPublicationsForPartNumber')
            ->with('EK-3333-01', $companyId)->willReturn($pubRows);

        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_clean();

        $preview = strpos($output, '<h2>Ingestion Preview</h2>');
        $list = strpos($output, '<ul>');
        $this->assertNotFalse($preview);
        $this->assertNotFalse($list);
        $this->assertLessThan($list, $preview);
        $this->assertStringContainsString('<td>Accepted</td>', $output);
        $this->assertStringContainsString(
            '<td><a href="details.php/13,23">Jumbotron Users Guide</a></td>',
            $output);
    }

    public function testRenderBodyContentAddsIngestControls()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $companyId = 13;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/pdp11', 150,
                    Manx\UrlMetaData::DEFAULT_PART_REGEX, 0]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')
            ->with($parentDirId)->willReturn($thisDirRows[0]);
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')
            ->with($siteName, $parentDirId)->willReturn([]);
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId],
                [223, 3, 'LSI-1_Systems_Service_Manual_Aug81.pdf',
                    0, 0, $parentDirId],
                [224, 3,
                    'EK-4444-01_Jumbotron_Reference_Manual_Feb1977.pdf',
                    0, 0, $parentDirId],
                [225, 3,
                    'EK-5555-01_New_System_Manual_Feb1977.pdf',
                    0, 0, $parentDirId]
            ]);
        $acceptedUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $rejectedUrl = 'http://bitsavers.org/pdf/dec/pdp11/LSI-1_Systems_Service_Manual_Aug81.pdf';
        $uncertainUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-4444-01_Jumbotron_Reference_Manual_Feb1977.pdf';
        $newUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-5555-01_New_System_Manual_Feb1977.pdf';
        $pubRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
            [
                [23, 'EK-3333-01', 'Jumbotron Users Guide', '1977-02']
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)->willReturn($fileRows);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn($companyId);
        $this->_db->expects($this->exactly(4))
            ->method('getFormatForExtension')
            ->withConsecutive(['pdf'], ['pdf'], ['pdf'], ['pdf'])
            ->willReturn('PDF', 'PDF', 'PDF', 'PDF');
        $this->_db->expects($this->exactly(4))->method('copyExistsForUrl')
            ->withConsecutive([$acceptedUrl], [$rejectedUrl], [$uncertainUrl],
                [$newUrl])
            ->willReturn(false, false, false, false);
        $this->_db->expects($this->exactly(3))
            ->method('getPublicationsForPartNumber')
            ->withConsecutive(['EK-3333-01', $companyId],
                ['EK-4444-01', $companyId], ['EK-5555-01', $companyId])
            ->willReturn(
                $pubRows,
                \Manx\Test\RowFactory::createResultRowsForColumns(
                    ['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
                    [
                        [31, 'EK-4444-01', 'Jumbotron Reference Manual', '1977-02'],
                        [32, 'EK-4444-01', 'Jumbotron Pocket Guide', '1978-04']
                    ]),
                []);

        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '<form id="ingest_preview_form" action="whatsnew.php" method="POST">',
            $output);
        $this->assertStringContainsString(
            '<td><a href="url-wizard.php?id=222&amp;url='
                . rawurlencode($acceptedUrl) . '">'
                . 'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf</a></td>',
            $output);
        preg_match_all('/name="ingest[0-9]+"/', $output, $matches);
        $this->assertCount(4, $matches[0]);
        $this->assertStringContainsString(
            '<input type="checkbox" id="ingest0" name="ingest0" value="222" checked="checked"/>',
            $output);
        $this->assertStringContainsString(
            '<input type="checkbox" id="ingest1" name="ingest1" value="223" disabled="disabled"/>',
            $output);
        $this->assertStringContainsString(
            '<input type="checkbox" id="ingest2" name="ingest2" value="224"/>',
            $output);
        $this->assertStringContainsString(
            '<input type="checkbox" id="ingest3" name="ingest3" value="225"/>',
            $output);
        $this->assertStringContainsString(
            'function showIngestPreviewStatusDetail(link)', $output);
        $this->assertStringContainsString(
            '<td><a href="#" onclick="showIngestPreviewStatusDetail(this); return false;" data-status-detail="The directory part-number regex did not match the filename.">Rejected</a></td>',
            $output);
        $this->assertStringContainsString(
            '<td><a href="#" onclick="showIngestPreviewStatusDetail(this); return false;" data-status-detail="The extracted part number EK-4444-01 matched 2 publications.">Uncertain</a></td>',
            $output);
        $this->assertStringContainsString(
            '<td><a href="#" onclick="showIngestPreviewStatusDetail(this); return false;" data-status-detail="No publication matched the extracted part number EK-5555-01.">New</a></td>',
            $output);
        $this->assertStringContainsString(
            'input[type="checkbox"][name^="ingest"]:not(:disabled)',
            $output);
        $this->assertStringContainsString(
            '<input type="button" id="ingest_check_all" value="Check All" onclick="setIngestPreviewChecked(true)" />',
            $output);
        $this->assertStringContainsString(
            '<input type="button" id="ingest_uncheck_all" value="Uncheck All" onclick="setIngestPreviewChecked(false)" />',
            $output);
        $this->assertStringContainsString(
            '<input type="submit" value="Ingest Selected" />', $output);
        $this->assertStringNotContainsString('name="part"', $output);
        $this->assertStringNotContainsString('name="pub_id"', $output);
        $this->assertStringNotContainsString('name="pub_date"', $output);
        $this->assertStringNotContainsString('name="title"', $output);
        $this->assertStringNotContainsString('name="format"', $output);
        $this->assertStringNotContainsString('name="copy_url"', $output);
    }

    public function testRenderBodyContentDisablesIngestControlsWithoutSelectableRows()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $companyId = 13;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/pdp11', 150,
                    Manx\UrlMetaData::DEFAULT_PART_REGEX, 0]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')
            ->with($parentDirId)->willReturn($thisDirRows[0]);
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')
            ->with($siteName, $parentDirId)->willReturn([]);
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId],
                [223, 3, 'LSI-1_Systems_Service_Manual_Aug81.pdf',
                    0, 0, $parentDirId]
            ]);
        $duplicateUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $rejectedUrl = 'http://bitsavers.org/pdf/dec/pdp11/LSI-1_Systems_Service_Manual_Aug81.pdf';
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)->willReturn($fileRows);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn($companyId);
        $this->_db->expects($this->exactly(2))
            ->method('getFormatForExtension')
            ->withConsecutive(['pdf'], ['pdf'])
            ->willReturn('PDF', 'PDF');
        $this->_db->expects($this->exactly(2))->method('copyExistsForUrl')
            ->withConsecutive([$duplicateUrl], [$rejectedUrl])
            ->willReturn(
                ['ph_company' => $companyId, 'ph_pub' => 23,
                    'ph_title' => 'Jumbotron Users Guide'],
                false);
        $this->_db->expects($this->never())
            ->method('getPublicationsForPartNumber');

        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '<input type="checkbox" id="ingest0" name="ingest0" value="222" disabled="disabled"/>',
            $output);
        $this->assertStringContainsString(
            '<input type="checkbox" id="ingest1" name="ingest1" value="223" disabled="disabled"/>',
            $output);
        $this->assertStringContainsString(
            '<input type="button" id="ingest_check_all" value="Check All" onclick="setIngestPreviewChecked(true)" disabled="disabled" />',
            $output);
        $this->assertStringContainsString(
            '<input type="button" id="ingest_uncheck_all" value="Uncheck All" onclick="setIngestPreviewChecked(false)" disabled="disabled" />',
            $output);
        $this->assertStringContainsString(
            '<input type="submit" value="Ingest Selected" disabled="disabled" />',
            $output);
    }

    public function testIngestPreviewRowsRecomputesSelectedAcceptedRows()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $companyId = 13;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId,
            'ingest_preview' => 1,
            'ingest0' => 222,
            'part' => 'WRONG',
            'pub_date' => '1900-01',
            'title' => 'Wrong Title',
            'pub_id' => 999,
            'copy_url' => 'http://example.test/wrong.pdf']);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/pdp11', 150,
                    Manx\UrlMetaData::DEFAULT_PART_REGEX, 0]
            ]);
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId],
                [223, 3,
                    'EK-4444-01_Jumbotron_Reference_Manual_Feb1977.pdf',
                    0, 0, $parentDirId]
            ]);
        $selectedUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $unselectedUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-4444-01_Jumbotron_Reference_Manual_Feb1977.pdf';
        $selectedPubs = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
            [
                [23, 'EK-3333-01', 'Jumbotron Users Guide', '1977-02']
            ]);
        $unselectedPubs = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['pub_id', 'ph_part', 'ph_title', 'ph_pub_date'],
            [
                [24, 'EK-4444-01', 'Jumbotron Reference Manual', '1977-02']
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')
            ->with($parentDirId)->willReturn($thisDirRows[0]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)->willReturn($fileRows);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn($companyId);
        $this->_db->expects($this->exactly(2))
            ->method('getFormatForExtension')
            ->withConsecutive(['pdf'], ['pdf'])
            ->willReturn('PDF', 'PDF');
        $this->_db->expects($this->exactly(2))->method('copyExistsForUrl')
            ->withConsecutive([$selectedUrl], [$unselectedUrl])
            ->willReturn(false, false);
        $this->_db->expects($this->exactly(2))
            ->method('getPublicationsForPartNumber')
            ->withConsecutive(['EK-3333-01', $companyId],
                ['EK-4444-01', $companyId])
            ->willReturn($selectedPubs, $unselectedPubs);
        $this->_db->expects($this->once())->method('addCopy')
            ->with(23, 'PDF', 3, $selectedUrl, '', 0, '', '', '')
            ->willReturn(884);
        $this->_db->expects($this->once())
            ->method('removeSiteUnknownPathsInDir')
            ->with([222], $parentDirId);

        $this->_page->ingestPreviewRows();
    }

    public function testIngestPreviewRowsSkipsRejectedRows()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $companyId = 13;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId,
            'ingest_preview' => 1,
            'ingest0' => 222,
            'ingest1' => 223]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/pdp11', 150,
                    Manx\UrlMetaData::DEFAULT_PART_REGEX, 0]
            ]);
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId],
                [223, 3, 'LSI-1_Systems_Service_Manual_Aug81.pdf',
                    0, 0, $parentDirId]
            ]);
        $duplicateUrl = 'http://bitsavers.org/pdf/dec/pdp11/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $rejectedUrl = 'http://bitsavers.org/pdf/dec/pdp11/LSI-1_Systems_Service_Manual_Aug81.pdf';
        $this->_db->expects($this->once())->method('getSiteUnknownDir')
            ->with($parentDirId)->willReturn($thisDirRows[0]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)->willReturn($fileRows);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn($companyId);
        $this->_db->expects($this->exactly(2))
            ->method('getFormatForExtension')
            ->withConsecutive(['pdf'], ['pdf'])
            ->willReturn('PDF', 'PDF');
        $this->_db->expects($this->exactly(2))->method('copyExistsForUrl')
            ->withConsecutive([$duplicateUrl], [$rejectedUrl])
            ->willReturn(
                ['ph_company' => $companyId, 'ph_pub' => 23,
                    'ph_title' => 'Jumbotron Users Guide'],
                false);
        $this->_db->expects($this->never())
            ->method('getPublicationsForPartNumber');
        $this->_db->expects($this->never())->method('addCopy');
        $this->_db->expects($this->never())->method('removeSiteUnknownPathsInDir');

        $this->_page->ingestPreviewRows();
    }

    public function testIngestPreviewRowsSkipsInvalidRegexRows()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId,
            'ingest_preview' => 1,
            'ingest0' => 222]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/pdp11', 150, '([broken', 0]
            ]);
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId]
            ]);
        $url = 'http://bitsavers.org/pdf/dec/pdp11/EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf';
        $this->_db->expects($this->once())->method('getSiteUnknownDir')
            ->with($parentDirId)->willReturn($thisDirRows[0]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)->willReturn($fileRows);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'dec/pdp11')->willReturn(13);
        $this->_db->expects($this->once())->method('getFormatForExtension')
            ->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->once())->method('copyExistsForUrl')
            ->with($url)->willReturn(false);
        $this->_db->expects($this->never())
            ->method('getPublicationsForPartNumber');
        $this->_db->expects($this->never())->method('addCopy');
        $this->_db->expects($this->never())->method('removeSiteUnknownPathsInDir');

        $this->_page->ingestPreviewRows();
    }

    public function testRenderBodyContentSkipsPreviewWithoutCompany()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $this->createPage(['siteName' => $siteName,
            'parentDir' => $parentDirId]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'unknown/path', 150,
                    Manx\UrlMetaData::DEFAULT_PART_REGEX, 0]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')
            ->with($parentDirId)->willReturn($thisDirRows[0]);
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')
            ->with($siteName, $parentDirId)->willReturn([]);
        $fileRows = \Manx\Test\RowFactory::createResultRowsForColumns(
            ['id', 'site_id', 'path', 'ignored', 'scanned', 'dir_id'],
            [
                [222, 3,
                    'EK-3333-01_Jumbotron_Users_Guide_Feb1977.pdf',
                    0, 0, $parentDirId]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')
            ->with($siteName, $parentDirId)->willReturn($fileRows);
        $this->_db->expects($this->once())
            ->method('getCompanyIdForSiteUnknownDir')
            ->with($siteName, 'unknown/path')->willReturn(-1);
        $this->_db->expects($this->once())->method('getFormatForExtension')
            ->with('pdf')->willReturn('PDF');
        $this->_db->expects($this->never())->method('copyExistsForUrl');
        $this->_db->expects($this->never())
            ->method('getPublicationsForPartNumber');

        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_clean();

        $this->assertStringNotContainsString('Ingestion Preview', $output);
    }

    public function testIgnorePaths()
    {
        $ignoredId = 111;
        $this->createPage(array('ignore0' => $ignoredId));
        $this->_db->expects($this->once())->method('ignoreSitePaths')->with([$ignoredId]);

        $this->_page->ignorePaths();
    }

    public function testIgnoreManyPaths()
    {
        $ignoredIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        $ignoreParams = [];
        $i = 0;
        foreach ($ignoredIds as $id)
        {
            $ignoreParams[sprintf('ignore%d', $i)] = $id;
            $i++;
        }
        $this->createPage($ignoreParams);
        $this->_db->expects($this->once())->method('ignoreSitePaths')->with($ignoredIds);

        $this->_page->ignorePaths();
    }

    public function testIgnoreMiddlePaths()
    {
        $ignoredIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        $ignoreParams = [];
        $i = 10;
        foreach ($ignoredIds as $id)
        {
            $ignoreParams[sprintf('ignore%d', $i)] = $id;
            $i++;
        }
        $this->createPage($ignoreParams);
        $this->_db->expects($this->once())->method('ignoreSitePaths')->with($ignoredIds);

        $this->_page->ignorePaths();
    }

}
