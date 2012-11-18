<?php

require_once 'pages/BitSaversPage.php';
require_once 'test/FakeBitSaversPageFactory.php';
require_once 'test/FakeManx.php';
require_once 'test/FakeManxDatabase.php';
require_once 'test/FakeUrlInfo.php';
require_once 'test/FakeUrlTransfer.php';

class FakeFile implements IFile
{
    private $_line;

    public function __construct()
    {
        $this->_line = 0;
        $this->getStringCalled = false;
        $this->getStringFakeResults = array();
    }

    function eof()
    {
        $this->eofCalled = true;
        return $this->_line >= count($this->getStringFakeResults);
    }
    public $eofCalled;

    function getString()
    {
        $this->getStringCalled = true;
        if ($this->_line < count($this->getStringFakeResults))
        {
            return $this->getStringFakeResults[$this->_line++];
        }
    }
    public $getStringCalled, $getStringFakeResults;
}

class BitSaversPageTester extends BitSaversPage
{
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

    public function renderPageSelectionBar($start, $total)
    {
        parent::renderPageSelectionBar($start, $total);
    }
}

class TestBitSaversPage extends PHPUnit_Framework_TestCase
{
    private $_vars;
    /** @var FakeManxDatabase */
    private $_db;
    /** @var FakeManx */
    private $_manx;
    /** @var FakeBitSaversPageFactory */
    private $_factory;
    /** @var FakeUrlInfo */
    private $_info;
    /** @var FakeUrlTransfer */
    private $_transfer;
    /** @var BitSaversPageTester */
    private $_page;
    /** @var FakeFile */
    private $_file;

    protected function setUp()
    {
        $this->_db = new FakeManxDatabase();
        $this->_manx = new FakeManx();
        $this->_manx->getDatabaseFakeResult = $this->_db;
        $this->_factory = new FakeBitSaversPageFactory();
        $this->_info = new FakeUrlInfo();
        $this->_factory->createUrlInfoFakeResult = $this->_info;
        $this->_transfer = new FakeUrlTransfer();
        $this->_factory->createUrlTransferFakeResult = $this->_transfer;
        $this->_file = new FakeFile();
        $this->_factory->openFileFakeResult = $this->_file;
    }

    public function testConstructWithNoTimeStampPropertyGetsWhatsNewFile()
    {
        $this->_db->getPropertyFakeResult = false;
        $paths = array('dec/1.pdf', 'dec/2.pdf', 'dec/3.pdf', 'dec/4.pdf', 'dec/5.pdf',
            'dec/6.pdf', 'dec/7.pdf', 'dec/8.pdf', 'dec/9.pdf', 'dec/A.pdf');
        $this->_file->getStringFakeResults = array_merge(array('======='), $paths);

        $this->createPage();

        $this->assertTrue(is_object($this->_page));
        $this->assertFalse(is_null($this->_page));
        $this->assertPropertyRead(TIMESTAMP_PROPERTY);
        $this->assertWhatsNewFileTransferred();
        $this->assertFileParsedPaths($paths);
    }

    public function testConstructWithNoLastModifiedGetsWhatsNewFile()
    {
        $this->_db->getPropertyFakeResult = '10';
        $this->_info->lastModifiedFakeResult = false;
        $this->_factory->getCurrentTimeFakeResult = '12';

        $this->createPage();

        $this->assertTrue($this->_factory->createUrlInfoCalled);
        $this->assertEquals(WHATS_NEW_URL, $this->_factory->createUrlInfoLastUrl);
        $this->assertTrue($this->_info->lastModifiedCalled);
        $this->assertWhatsNewFileTransferred();
    }

    public function testConstructWithLastModifiedEqualsTimeStampDoesNotGetWhatsNewFile()
    {
        $this->_db->getPropertyFakeResult = '10';
        $this->_info->lastModifiedFakeResult = '10';

        $this->createPage();

        $this->assertFalse($this->_factory->createUrlTransferCalled);
    }

    public function testConstructWithLastModifiedNewerThanTimeStampGetsWhatsNewFile()
    {
        $this->_db->getPropertyFakeResult = '10';
        $this->_info->lastModifiedFakeResult = '20';

        $this->createPage();

        $this->assertWhatsNewFileTransferred();
    }

    public function testMenuTypeIsBitSaversPage()
    {
        $this->createPageWithoutFetchingWhatsNewFile();
        $this->assertEquals(MenuType::BitSavers, $this->_page->getMenuType());
    }

    public function testRenderBodyContentWithPlentyOfPaths()
    {
        $this->createPageWithoutFetchingWhatsNewFile();
        $this->_db->getBitSaversUnknownPathCountFakeResult = 10;
        $paths = array('dec/1.pdf', 'dec/2.pdf', 'dec/3.pdf', 'dec/4.pdf', 'dec/5.pdf',
            'dec/6.pdf', 'dec/7.pdf', 'dec/8.pdf', 'dec/9.pdf', 'dec/A.pdf');
        $this->_db->getBitSaversUnknownPathsFakeResult = self::createResultRowsForUnknownPaths($paths);
        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertTrue($this->_db->getBitSaversUnknownPathCountCalled);
        $this->assertTrue($this->_db->getBitSaversUnknownPathsCalled);
        $this->assertEquals(0, $this->_db->getBitSaversUnknownPathsLastStart);
        $this->assertEquals(self::expectedOutputForPaths($paths), $output);
    }

    public function testRenderBodyContentGetsNewPaths()
    {
        $this->createPageWithoutFetchingWhatsNewFile();
        $paths = array('dec/1.pdf', 'dec/2.pdf', 'dec/3.pdf', 'dec/4.pdf', 'dec/5.pdf',
            'dec/6.pdf', 'dec/7.pdf', 'dec/8.pdf', 'dec/9.pdf', 'dec/A.pdf');
        $this->_db->getBitSaversUnknownPathCountFakeResult = count($paths);
        $this->configureCopiesExistForPaths($paths);
        $this->_db->getBitSaversUnknownPathsFakeResult = self::createResultRowsForUnknownPaths($paths);
        ob_start();

        $this->_page->renderBodyContent();

        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(self::expectedOutputForPaths($paths), $output);
    }

    public function testIgnorePaths()
    {
        $ignoredPath = 'dec/1.pdf';
        $this->createPageWithoutFetchingWhatsNewFile(array('ignore0' => $ignoredPath));
        $this->_page->ignorePaths();
        $this->assertTrue($this->_db->ignoreBitSaversPathCalled);
        $this->assertEquals($ignoredPath, $this->_db->ignoreBitSaversPathLastPath);
    }

    public function testRenderPageSelectionBarOnePage()
    {
        $this->createPageWithoutFetchingWhatsNewFile(array('start' => 0));
        ob_start();
        $this->_page->renderPageSelectionBar(0, 10);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;</div>' . "\n",
            $output);
    }

    public function testRenderBodyContentNoDocuments()
    {
        $this->createPageWithoutFetchingWhatsNewFile();
        $this->_db->getBitSaversUnknownPathCountFakeResult = 0;
        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals("<h1>No New BitSavers Publications Found</h1>\n", $output);
    }

    private function assertFileParsedPaths($paths)
    {
        $this->assertTrue($this->_factory->openFileCalled);
        $this->assertEquals(WHATS_NEW_FILE, $this->_factory->openFileLastPath);
        $this->assertEquals('r', $this->_factory->openFileLastMode);
        $this->assertTrue($this->_file->eofCalled);
        $this->assertTrue($this->_file->getStringCalled);
        $this->assertTrue($this->_db->copyExistsForUrlCalled);
        $this->assertTrue($this->_db->addBitSaversUnknownPathCalled);
        foreach ($paths as $path)
        {
            $this->assertContains($path, $this->_db->addBitSaversUnknownPathLastPaths);
        }
    }

    private static function createResultRowsForUnknownPaths($items)
    {
        $id = 1;
        for ($i = 0; $i < count($items); ++$i)
        {
            $items[$i] = array($id++, $items[$i]);
        }
        return FakeDatabase::createResultRowsForColumns(array('id', 'path'), $items);
    }

    private function configureCopiesExistForPaths($paths)
    {
        $existing = array();
        foreach ($paths as $path)
        {
            $existing['http://bitsavers.trailing-edge.com/pdf/' . $path] = true;
        }
        $this->_db->copyExistsForUrlFakeResults = $existing;
    }

    private static function expectedOutputForPaths($paths)
    {
        $expected = <<<EOH
<h1>New BitSavers Publications</h1>

<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;</div>
<form action="bitsavers.php" method="POST">
<input type="hidden" name="start" value="0" />
<table>

EOH;
        $i = 0;
        $n = 1;
        foreach ($paths as $path)
        {
            $item = <<<EOH
<tr><td>$n.</td><td><input type="checkbox" id="ignore$i" name="ignore$i" value="$path" />
<a href="url-wizard.php?url=http://bitsavers.trailing-edge.com/pdf/$path">$path</a></td></tr>

EOH;
            $expected = $expected . $item;
            ++$i;
            ++$n;
        }
        $trailer = <<<EOH
</table>
<input type="submit" value="Ignore" />
</form>

EOH;
        $expected = $expected . $trailer;
        return $expected;
    }

    private function createPageWithoutFetchingWhatsNewFile($vars = array())
    {
        $this->_db->getPropertyFakeResult = '10';
        $this->_info->lastModifiedFakeResult = '10';
        $this->createPage($vars);
    }

    private function createPage($vars = array())
    {
        $_SERVER['PATH_INFO'] = '';
        $this->_vars = $vars;
        $this->_page = new BitSaversPageTester($this->_manx, $this->_vars, $this->_factory);
    }

    private function assertPropertyRead($name)
    {
        $this->assertTrue($this->_db->getPropertyCalled);
        $this->assertEquals($name, $this->_db->getPropertyLastName);
    }

    private function assertWhatsNewFileTransferred()
    {
        $this->assertTrue($this->_factory->createUrlTransferCalled);
        $this->assertEquals(WHATS_NEW_URL, $this->_factory->createUrlTransferLastUrl);
        $this->assertTrue($this->_transfer->getCalled);
        $this->assertEquals(WHATS_NEW_FILE, $this->_transfer->getLastDestination);
        $this->assertTrue($this->_db->setPropertyCalled);
    }
}
