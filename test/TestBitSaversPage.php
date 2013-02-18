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

    public function renderPageSelectionBar($start, $total, $sortById)
    {
        parent::renderPageSelectionBar($start, $total, $sortById);
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
        $idStart = 110;
        $this->_db->getBitSaversUnknownPathsOrderedByIdFakeResult =
            self::createResultRowsForUnknownPaths($paths, $idStart);
        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertTrue($this->_db->getBitSaversUnknownPathCountCalled);
        $this->assertTrue($this->_db->getBitSaversUnknownPathsOrderedByIdCalled);
        $this->assertEquals(0, $this->_db->getBitSaversUnknownPathsOrderedByIdLastStart);
        $this->assertEquals(self::expectedOutputForPaths($paths, $idStart), $output);
    }

    public function testRenderBodyContentWithPlentyOfPathsOrderedByPath()
    {
        $this->createPageWithoutFetchingWhatsNewFile(array('sort' => SORT_ORDER_BY_PATH));
        $this->_db->getBitSaversUnknownPathCountFakeResult = 10;
        $paths = array('dec/Z.pdf', 'dec/Y.pdf', 'dec/X.pdf', 'dec/W.pdf', 'dec/V.pdf',
            'dec/U.pdf', 'dec/T.pdf', 'dec/S.pdf', 'dec/R.pdf', 'dec/Q.pdf');
        $idStart = 110;
        $this->_db->getBitSaversUnknownPathsOrderedByPathFakeResult =
            self::createResultRowsForUnknownPaths($paths, $idStart);
        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertTrue($this->_db->getBitSaversUnknownPathCountCalled);
        $this->assertTrue($this->_db->getBitSaversUnknownPathsOrderedByPathCalled);
        $this->assertEquals(0, $this->_db->getBitSaversUnknownPathsOrderedByIdLastStart);
        $this->assertEquals(self::expectedOutputForPaths($paths, $idStart, false), $output);
    }

    public function testRenderBodyContentGetsNewPaths()
    {
        $this->createPageWithoutFetchingWhatsNewFile();
        $paths = array('dec/1.pdf', 'dec/2.pdf', 'dec/3.pdf', 'dec/4.pdf', 'dec/5.pdf',
            'dec/6.pdf', 'dec/7.pdf', 'dec/8.pdf', 'dec/9.pdf', 'dec/A.pdf');
        $this->_db->getBitSaversUnknownPathCountFakeResult = count($paths);
        $this->configureCopiesExistForPaths($paths);
        $this->_db->getBitSaversUnknownPathsOrderedByIdFakeResult =
            self::createResultRowsForUnknownPaths($paths);
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
        $this->_page->renderPageSelectionBar(0, 10, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyPages()
    {
        $this->createPageWithoutFetchingWhatsNewFile(array('start' => 0));
        ob_start();
        $this->_page->renderPageSelectionBar(0, 1234, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10">2</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=20">3</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=30">4</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=40">5</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=50">6</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=60">7</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=70">8</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=80">9</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=90">10</a>&nbsp;&nbsp;'
                . '<a href="bitsavers.php?start=10"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1000"><b>&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyManyPages()
    {
        $this->createPageWithoutFetchingWhatsNewFile(array('start' => 0));
        ob_start();
        $this->_page->renderPageSelectionBar(0, 12340, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10">2</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=20">3</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=30">4</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=40">5</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=50">6</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=60">7</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=70">8</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=80">9</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=90">10</a>&nbsp;&nbsp;'
                . '<a href="bitsavers.php?start=10"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1000"><b>&gt;</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10000"><b>&gt;&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyPreviousPages()
    {
        $this->createPageWithoutFetchingWhatsNewFile(array('start' => 1100));
        ob_start();
        $this->_page->renderPageSelectionBar(1100, 1234, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=100"><b>&lt;</b></a>&nbsp;&nbsp;'
                . '<a href="bitsavers.php?start=1090"><b>Previous</b></a>&nbsp;&nbsp;'
                . '<b class="currpage">111</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1110">112</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1120">113</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1130">114</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1140">115</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1150">116</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1160">117</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1170">118</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1180">119</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1190">120</a>&nbsp;&nbsp;'
                . '<a href="bitsavers.php?start=1110"><b>Next</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBar10KPreviousPages()
    {
        $this->createPageWithoutFetchingWhatsNewFile(array('start' => 10000));
        ob_start();
        $this->_page->renderPageSelectionBar(10000, 12340, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=0"><b>&lt;&lt;</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=9000"><b>&lt;</b></a>&nbsp;&nbsp;'
                . '<a href="bitsavers.php?start=9990"><b>Previous</b></a>&nbsp;&nbsp;'
                . '<b class="currpage">1001</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10010">1002</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10020">1003</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10030">1004</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10040">1005</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10050">1006</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10060">1007</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10070">1008</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10080">1009</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10090">1010</a>&nbsp;&nbsp;'
                . '<a href="bitsavers.php?start=10010"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11000"><b>&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyManyPreviousPages()
    {
        $this->createPageWithoutFetchingWhatsNewFile(array('start' => 11000));
        ob_start();
        $this->_page->renderPageSelectionBar(11000, 12340, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1000"><b>&lt;&lt;</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10000"><b>&lt;</b></a>&nbsp;&nbsp;'
                . '<a href="bitsavers.php?start=10990"><b>Previous</b></a>&nbsp;&nbsp;'
                . '<b class="currpage">1101</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11010">1102</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11020">1103</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11030">1104</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11040">1105</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11050">1106</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11060">1107</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11070">1108</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11080">1109</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=11090">1110</a>&nbsp;&nbsp;'
                . '<a href="bitsavers.php?start=11010"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=12000"><b>&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyPagesByPath()
    {
        $this->createPageWithoutFetchingWhatsNewFile(array('start' => 0, 'sort' => SORT_ORDER_BY_PATH));
        ob_start();
        $this->_page->renderPageSelectionBar(0, 1234, false);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=10&sort=bypath">2</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=20&sort=bypath">3</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=30&sort=bypath">4</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=40&sort=bypath">5</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=50&sort=bypath">6</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=60&sort=bypath">7</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=70&sort=bypath">8</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=80&sort=bypath">9</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=90&sort=bypath">10</a>&nbsp;&nbsp;'
                . '<a href="bitsavers.php?start=10&sort=bypath"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="bitsavers.php?start=1000&sort=bypath"><b>&gt;</b></a>'
                . '</div>' . "\n",
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

    private static function createResultRowsForUnknownPaths($items, $idStart = 1)
    {
        $id = $idStart;
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

    private static function expectedOutputForPaths($paths, $idStart = 1, $sortById = true)
    {
        if ($sortById)
        {
            $expectedIdHeader = 'Id';
            $expectedPathHeader = '<a href="bitsavers.php?sort=bypath">Path</a>';
        }
        else
        {
            $expectedIdHeader = '<a href="bitsavers.php?sort=byid">Id</a>';
            $expectedPathHeader = 'Path';
        }

        $expected = <<<EOH
<h1>New BitSavers Publications</h1>

<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;</div>
<form action="bitsavers.php" method="POST">
<input type="hidden" name="start" value="0" />
<table>
<tr><th>$expectedIdHeader</th><th>$expectedPathHeader</th></tr>

EOH;
        $i = 0;
        $n = $idStart;
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
