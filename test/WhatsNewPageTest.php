<?php

require_once __DIR__ . '/../vendor/autoload.php';

// For SORT_ORDER_xxx
require_once __DIR__ . '/../public/pages/UnknownPathDefs.php';

use Pimple\Container;

class WhatsNewPageTester extends Manx\WhatsNewPage
{
    // lift visibility of some functions for testing
    public function getMenuType()
    {
        return parent::getMenuType();
    }

    public function renderBodyContent()
    {
        parent::renderBodyContent();
    }

    public function ignorePaths()
    {
        parent::ignorePaths();
    }
}

class TestWhatsNewPage extends PHPUnit\Framework\TestCase
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

    protected function setUp()
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

    public function testRenderBodyContentNoRootPaths()
    {
        $siteName = 'bitsavers';
        $parentDirId = -1;
        $this->createPage(['siteName' => $siteName, 'parentDir' => $parentDirId]);
        $this->_db->expects($this->never())->method('getSiteUnknownDir');
        $this->_db->expects($this->once())->method('getSiteUnknownDirectories')->with($siteName, $parentDirId)->willReturn([]);
        $this->_db->expects($this->once())->method('getSiteUnknownPaths')->with($siteName, $parentDirId)->willReturn([]);

        $this->_page->renderBodyContent();

        $this->expectOutputString("<h1>No New BitSavers Publications Found</h1>\n");
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

<ul>
<li><a href="whatsnew.php?site=bitsavers&parentDir=150">(parent)</a></li>
</ul>

EOH;
        $this->expectOutputString($expected);
    }

    public function testRenderBodyContent()
    {
        $siteName = 'bitsavers';
        $parentDirId = 1339;
        $this->createPage(['siteName' => $siteName, 'parentDir' => $parentDirId]);
        $thisDirRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [100, 3, 'dec/pdp11', 150, '', 0]
            ]);
        $this->_db->expects($this->once())->method('getSiteUnknownDir')->with($parentDirId)->willReturn($thisDirRows[0]);
        $dirRows = \Manx\Test\RowFactory::createResultRowsForColumns(['id', 'site_id', 'path', 'parent_dir_id', 'part_regex', 'ignored'],
            [
                [111, 3, 'dec/pdp11/1103', 1339, '', 0],
                [112, 3, 'dec/pdp11/1104', 1339, '', 0],
                [113, 3, 'dec/pdp11/1105', 1339, '', 0],
                [114, 3, 'dec/pdp11/photos', 1339, '', 1],
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

<ul>
<li><a href="whatsnew.php?site=bitsavers&parentDir=150">(parent)</a></li>
<li><a href="whatsnew.php?site=bitsavers&parentDir=111">dec/pdp11/1103</a></li>
<li><a href="whatsnew.php?site=bitsavers&parentDir=112">dec/pdp11/1104</a></li>
<li><a href="whatsnew.php?site=bitsavers&parentDir=113">dec/pdp11/1105</a></li>
</ul>
<form action="whatsnew.php" method="POST">
<input type="hidden" name="site" value="bitsavers" />
<input type="hidden" name="parentDir" value="1339" />
<table>
<tr><th>Ignored?</th><th>File</th></tr>
<tr><td><input type="checkbox" id="ignore0" name="ignore0" value="222"/></td>
<td><a href="url-wizard.php?id=222&url=http://bitsavers.org/pdf/dec/pdp11/KM11_Maintenance_Panel_May70.pdf">KM11_Maintenance_Panel_May70.pdf</a></td></tr>
<tr><td><input type="checkbox" id="ignore1" name="ignore1" value="223"/></td>
<td><a href="url-wizard.php?id=223&url=http://bitsavers.org/pdf/dec/pdp11/EK0LSIFS-SV-005_LSI-11_Systems_Service_Manual_Volume_3_Jan85.pdf">EK0LSIFS-SV-005_LSI-11_Systems_Service_Manual_Volume_3_Jan85.pdf</a></td></tr>
<tr><td><input type="checkbox" id="ignore2" name="ignore2" value="224"/></td>
<td><a href="url-wizard.php?id=224&url=http://bitsavers.org/pdf/dec/pdp11/LSI-11_Systems_Service_Manual_Aug81.pdf">LSI-11_Systems_Service_Manual_Aug81.pdf</a></td></tr>
<tr><td><input type="checkbox" id="ignore3" name="ignore3" value="225" checked/></td>
<td><a href="url-wizard.php?id=225&url=http://bitsavers.org/pdf/dec/pdp11/firmware.zip">firmware.zip</a></td></tr>
</table>
<input type="submit" value="Ignore" />
</form>

EOH;
        $this->expectOutputString($expected);
    }

    public function testIgnorePaths()
    {
        $ignoredId = 111;
        $this->createPage(array('ignore0' => $ignoredId));
        $this->_db->expects($this->once())->method('ignoreSitePaths')->with([$ignoredId]);

        $this->_page->ignorePaths();
    }

    public function testPoundSignEscaped()
    {
        $this->assertEquals("microCornucopia/Micro_Cornucopia_%2350_Nov89.pdf",
            Manx\WhatsNewPage::escapeSpecialChars("microCornucopia/Micro_Cornucopia_#50_Nov89.pdf"));
    }

    public function testSpaceEscaped()
    {
        $this->assertEquals("microCornucopia/Micro_Cornucopia%2050_Nov89.pdf",
            Manx\WhatsNewPage::escapeSpecialChars("microCornucopia/Micro_Cornucopia 50_Nov89.pdf"));
    }
}
