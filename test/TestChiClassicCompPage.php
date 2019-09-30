<?php

require_once 'pages/ChiClassicCompPage.php';
require_once 'test/FakeFile.php';
require_once 'test/FakeManxDatabase.php';

class ChiClassicCompPageTester extends ChiClassicCompPage
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

class TestChiClassicCompPage extends PHPUnit\Framework\TestCase
{
    private $_vars;
    /** @var FakeManxDatabase */
    private $_db;
    /** @var IManx */
    private $_manx;
    /** @var IFileSystem */
    private $_fileSystem;
    /** @var IWhatsNewPageFactory */
    private $_factory;
    /** @var IUrlInfo */
    private $_info;
    /** @var IUrlTransfer */
    private $_transfer;
    /** @var ChiClassicCompPageTester */
    private $_page;
    /** @var FakeFile */
    private $_file;

    protected function setUp()
    {
        $this->_db = new FakeManxDatabase();
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->method('getDatabase')->willReturn($this->_db);
        $this->_fileSystem = $this->createMock(IFileSystem::class);
        $this->_factory = $this->createMOck(IWhatsNewPageFactory::class);
        $this->_info = $this->createMock(IUrlInfo::class);
        $this->_factory->createUrlInfoFakeResult = $this->_info;
        $this->_transfer = $this->createMock(IUrlTransfer::class);
        $this->_file = new FakeFile();
        $this->_db->getFormatForExtensionFakeResults['pdf'] = 'PDF';
    }

    public function testConstructWithNoTimeStampPropertyGetsIndexByDateFile()
    {
        $this->_db->getPropertyFakeResult = false;
        $paths = array(
            'computing/_Punchedcards/Sears.jpg',
            'computing/_Punchedcards/harrisbankhollerithcard2.png',
            'computing/DEC/Pathworks/AA-MF87D-TH_PathworksDOSWindowsSupportGuide_Aug91.pdf',
            'computing/Morrow/Morrow_TRICEPBrochure.pdf',
            'telephony/BellSystem/512-740-405-I03_7028+7028M+27028+27028MSetsSpeakerphone4A_Apr79.pdf',
            'telephony/BellSystem/512-700-100-I2_4ASpeakerphoneSystem_Sept74.pdf',
            'telephony/BellSystem/512-700-100-I4_4ASpeakerphoneSystem_May78.pdf',
            'telephony/BellSystem/100-100-101_35-TypeTestSets.pdf',
            'computing/IBM/Mainframe/Hardware/System/GC20-2021-2_Guide4381Processor_Apr86.pdf',
            'telephony/SouthernBell/PTC320-L1_Relay&ApparatusAdjustment_BellSystemPracticesUnit1.pdf'
        );
        $lines = array();
        foreach ($paths as $path)
        {
            array_push($lines, '2013-10-07 21:02:00 ' . $path);
        }
        $this->_file->getStringFakeResults = array_merge($lines);
        $this->expectIndexFileTransferred();
        $this->expectIndexFileOpened();

        $this->createPage();

        $this->assertTrue(is_object($this->_page));
        $this->assertFalse(is_null($this->_page));
        $this->assertPropertyRead(CCC_TIMESTAMP_PROPERTY);
        $this->assertIndexByDateFileTransferred();
        $this->assertFileParsedPaths($paths);
    }

    public function testConstructWithNoLastModifiedGetsIndexByDateFile()
    {
        $this->_db->getPropertyFakeResult = '10';
        $this->_info->expects($this->once())->method('lastModified')->willReturn(false);
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with($this->equalTo(CCC_INDEX_BY_DATE_URL))->willReturn($this->_info);
        $this->_factory->method('getCurrentTime')->willReturn('12');
        $this->expectIndexFileTransferred();
        $this->expectIndexFileOpened();

        $this->createPage();

        $this->assertIndexByDateFileTransferred();
    }

    public function testConstructWithLastModifiedEqualsTimeStampDoesNotGetIndexByDateFile()
    {
        $this->_db->getPropertyFakeResult = '10';
        $this->_info->expects($this->once())->method('lastModified')->willReturn('10');
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with($this->equalTo(CCC_INDEX_BY_DATE_URL))->willReturn($this->_info);

        $this->createPage();
    }

    public function testConstructWithLastModifiedNewerThanTimeStampGetsIndexByDateFile()
    {
        $this->_db->getPropertyFakeResult = '10';
        $this->_info->expects($this->once())->method('lastModified')->willReturn('20');
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with($this->equalTo(CCC_INDEX_BY_DATE_URL))->willReturn($this->_info);
        $this->expectIndexFileTransferred();
        $this->expectIndexFileOpened();

        $this->createPage();

        $this->assertIndexByDateFileTransferred();
    }

    public function testMenuTypeIsChiClassicCompPage()
    {
        $this->createPageWithoutFetchingIndexByDateFile();

        $this->assertEquals(MenuType::ChiClassicComp, $this->_page->getMenuType());
    }

    public function testRenderBodyContentWithPlentyOfPaths()
    {
        $this->createPageWithoutFetchingIndexByDateFile();
        $this->_db->getSiteUnknownPathCountFakeResult = 10;
        $paths = array('dec/1.pdf', 'dec/2.pdf', 'dec/3.pdf', 'dec/4.pdf', 'dec/5.pdf',
            'dec/6.pdf', 'dec/7.pdf', 'dec/8.pdf', 'dec/9.pdf', 'dec/A#A.pdf');
        $idStart = 110;
        $this->_db->getSiteUnknownPathsOrderedByIdFakeResult =
            self::createResultRowsForUnknownPaths($paths, $idStart);

        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($this->_db->getSiteUnknownPathCountCalled);
        $this->assertTrue($this->_db->getSiteUnknownPathsOrderedByIdCalled);
        $this->assertEquals(0, $this->_db->getSiteUnknownPathsOrderedByIdLastStart);
        $this->assertEquals(self::expectedOutputForPaths($paths, $idStart), $output);
    }

    public function testRenderBodyContentWithIgnoredPaths()
    {
        $this->createPageWithoutFetchingIndexByDateFile();
        $this->_db->getSiteUnknownPathCountFakeResult = 10;
        $paths = array('dec/1.bin', 'dec/2.zip', 'dec/3.dat', 'dec/4.u6', 'dec/5.tar',
            'dec/6.gz', 'dec/7.jpg', 'dec/8.gif', 'dec/9.tif', 'dec/A#A.png');
        $idStart = 110;
        $this->_db->getSiteUnknownPathsOrderedByIdFakeResult =
            self::createResultRowsForUnknownPaths($paths, $idStart);
        $this->_db->getFormatForExtensionFakeResults =
            array('pdf' => 'PDF', 'jpg' => 'JPEG', 'gif' => 'GIF', 'png' => 'PNG', 'tif' => 'TIFF');
        $checks = array('checked', 'checked', 'checked', 'checked', 'checked',
            'checked', 'checked', 'checked', 'checked', 'checked');

        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($this->_db->getSiteUnknownPathCountCalled);
        $this->assertTrue($this->_db->getSiteUnknownPathsOrderedByIdCalled);
        $this->assertEquals(0, $this->_db->getSiteUnknownPathsOrderedByIdLastStart);
        $this->assertEquals(self::expectedOutputForCheckedPaths($paths, $checks, $idStart), $output);
    }

    public function testRenderBodyContentWithPlentyOfPathsOrderedByPath()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('sort' => SORT_ORDER_BY_PATH));
        $this->_db->getSiteUnknownPathCountFakeResult = 10;
        $paths = array('dec/Q.pdf', 'dec/R.pdf', 'dec/S.pdf', 'dec/T.pdf', 'dec/U.pdf',
            'dec/V.pdf', 'dec/W.pdf', 'dec/X.pdf', 'dec/Y.pdf', 'dec/Z.pdf');
        $idStart = 110;
        $this->_db->getSiteUnknownPathsOrderedByPathFakeResult =
            self::createResultRowsForUnknownPaths($paths, $idStart);

        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($this->_db->getSiteUnknownPathCountCalled);
        $this->assertTrue($this->_db->getSiteUnknownPathsOrderedByPathCalled);
        $this->assertTrue($this->_db->getSiteUnknownPathsOrderedByPathLastAscending);
        $this->assertEquals(0, $this->_db->getSiteUnknownPathsOrderedByIdLastStart);
        $this->assertEquals(self::expectedOutputForPaths($paths, $idStart, false), $output);
    }

    public function testRenderBodyContentWithPlentyOfPathsOrderedByPathDescending()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('sort' => SORT_ORDER_BY_PATH_DESCENDING));
        $this->_db->getSiteUnknownPathCountFakeResult = 10;
        $paths = array('dec/Z.pdf', 'dec/Y.pdf', 'dec/X.pdf', 'dec/W.pdf', 'dec/V.pdf',
            'dec/U.pdf', 'dec/T.pdf', 'dec/S.pdf', 'dec/R.pdf', 'dec/Q.pdf');
        $idStart = 110;
        $this->_db->getSiteUnknownPathsOrderedByPathFakeResult =
            self::createResultRowsForUnknownPaths($paths, $idStart);

        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($this->_db->getSiteUnknownPathCountCalled);
        $this->assertTrue($this->_db->getSiteUnknownPathsOrderedByPathCalled);
        $this->assertFalse($this->_db->getSiteUnknownPathsOrderedByPathLastAscending);
        $this->assertEquals(0, $this->_db->getSiteUnknownPathsOrderedByIdLastStart);
        $this->assertEquals(self::expectedOutputForPaths($paths, $idStart, false, false), $output);
    }

    public function testRenderBodyContentGetsNewPaths()
    {
        $this->createPageWithoutFetchingIndexByDateFile();
        $paths = array('dec/1.pdf', 'dec/2.pdf', 'dec/3.pdf', 'dec/4.pdf', 'dec/5.pdf',
            'dec/6.pdf', 'dec/7.pdf', 'dec/8.pdf', 'dec/9.pdf', 'dec/A.pdf');
        $this->_db->getSiteUnknownPathCountFakeResult = count($paths);
        $this->configureCopiesExistForPaths($paths);
        $this->_db->getSiteUnknownPathsOrderedByIdFakeResult =
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
        $this->createPageWithoutFetchingIndexByDateFile(array('ignore0' => $ignoredPath));
        $this->_page->ignorePaths();
        $this->assertTrue($this->_db->ignoreSitePathCalled);
        $this->assertEquals('ChiClassicComp', $this->_db->ignoreSitePathLastSiteName);
        $this->assertEquals($ignoredPath, $this->_db->ignoreSitePathLastPath);
    }

    public function testRenderPageSelectionBarOnePage()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('start' => 0, 'sort' => SORT_ORDER_BY_ID));

        ob_start();
        $this->_page->renderPageSelectionBar(0, 10);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyPages()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('start' => 0, 'sort' => SORT_ORDER_BY_ID));

        ob_start();
        $this->_page->renderPageSelectionBar(0, 1234);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10">2</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=20">3</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=30">4</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=40">5</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=50">6</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=60">7</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=70">8</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=80">9</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=90">10</a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=10"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1000"><b>&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyManyPages()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('start' => 0, 'sort' => SORT_ORDER_BY_ID));

        ob_start();
        $this->_page->renderPageSelectionBar(0, 12340, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10">2</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=20">3</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=30">4</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=40">5</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=50">6</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=60">7</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=70">8</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=80">9</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=90">10</a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=10"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1000"><b>&gt;</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10000"><b>&gt;&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyPreviousPages()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('start' => 1100, 'sort' => SORT_ORDER_BY_ID));

        ob_start();
        $this->_page->renderPageSelectionBar(1100, 1234, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=100"><b>&lt;</b></a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=1090"><b>Previous</b></a>&nbsp;&nbsp;'
                . '<b class="currpage">111</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1110">112</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1120">113</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1130">114</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1140">115</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1150">116</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1160">117</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1170">118</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1180">119</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1190">120</a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=1110"><b>Next</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBar10KPreviousPages()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('start' => 10000, 'sort' => SORT_ORDER_BY_ID));

        ob_start();
        $this->_page->renderPageSelectionBar(10000, 12340, true);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=0"><b>&lt;&lt;</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=9000"><b>&lt;</b></a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=9990"><b>Previous</b></a>&nbsp;&nbsp;'
                . '<b class="currpage">1001</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10010">1002</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10020">1003</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10030">1004</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10040">1005</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10050">1006</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10060">1007</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10070">1008</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10080">1009</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10090">1010</a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=10010"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11000"><b>&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyManyPreviousPages()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('start' => 11000, 'sort' => SORT_ORDER_BY_ID));

        ob_start();
        $this->_page->renderPageSelectionBar(11000, 12340);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1000"><b>&lt;&lt;</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10000"><b>&lt;</b></a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=10990"><b>Previous</b></a>&nbsp;&nbsp;'
                . '<b class="currpage">1101</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11010">1102</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11020">1103</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11030">1104</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11040">1105</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11050">1106</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11060">1107</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11070">1108</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11080">1109</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=11090">1110</a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=11010"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=12000"><b>&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testPoundSignEscaped()
    {
        $this->assertEquals("microCornucopia/Micro_Cornucopia_%2350_Nov89.pdf",
            ChiClassicCompPage::escapeSpecialChars("microCornucopia/Micro_Cornucopia_#50_Nov89.pdf"));
    }

    public function testSpaceEscaped()
    {
        $this->assertEquals("microCornucopia/Micro_Cornucopia%2050_Nov89.pdf",
            ChiClassicCompPage::escapeSpecialChars("microCornucopia/Micro_Cornucopia 50_Nov89.pdf"));
    }

    public function testRenderPageSelectionBarManyPagesByPath()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('start' => 0, 'sort' => SORT_ORDER_BY_PATH));

        ob_start();
        $this->_page->renderPageSelectionBar(0, 1234);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10&sort=bypath">2</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=20&sort=bypath">3</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=30&sort=bypath">4</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=40&sort=bypath">5</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=50&sort=bypath">6</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=60&sort=bypath">7</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=70&sort=bypath">8</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=80&sort=bypath">9</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=90&sort=bypath">10</a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=10&sort=bypath"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1000&sort=bypath"><b>&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderPageSelectionBarManyPagesByPathDescending()
    {
        $this->createPageWithoutFetchingIndexByDateFile(array('start' => 0, 'sort' => SORT_ORDER_BY_PATH_DESCENDING));

        ob_start();
        $this->_page->renderPageSelectionBar(0, 1234);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            '<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=10&sort=bypathdesc">2</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=20&sort=bypathdesc">3</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=30&sort=bypathdesc">4</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=40&sort=bypathdesc">5</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=50&sort=bypathdesc">6</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=60&sort=bypathdesc">7</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=70&sort=bypathdesc">8</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=80&sort=bypathdesc">9</a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=90&sort=bypathdesc">10</a>&nbsp;&nbsp;'
                . '<a href="chiclassiccomp.php?start=10&sort=bypathdesc"><b>Next</b></a>&nbsp;&nbsp;'
                . '<a class="navpage" href="chiclassiccomp.php?start=1000&sort=bypathdesc"><b>&gt;</b></a>'
                . '</div>' . "\n",
            $output);
    }

    public function testRenderBodyContentNoDocuments()
    {
        $this->createPageWithoutFetchingIndexByDateFile();
        $this->_db->getSiteUnknownPathCountFakeResult = 0;

        ob_start();
        $this->_page->renderBodyContent();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("<h1>No New ChiClassicComp Publications Found</h1>\n", $output);
    }

    private function expectIndexFileTransferred()
    {
        $this->_transfer->expects($this->once())->method('get')
            ->with($this->equalTo(PRIVATE_DIR . CCC_INDEX_BY_DATE_FILE));
        $this->_factory->expects($this->once())->method('createUrlTransfer')
            ->with($this->equalTo(CCC_INDEX_BY_DATE_URL))->wilLReturn($this->_transfer);
    }

    private function expectIndexFileOpened()
    {
        $this->_fileSystem->expects($this->once())->method('openFile')
            ->with($this->equalTo(PRIVATE_DIR . CCC_INDEX_BY_DATE_FILE), $this->equalTo('r'))
            ->willReturn($this->_file);
    }

    private function assertFileParsedPaths($paths)
    {
        $this->assertTrue($this->_file->eofCalled);
        $this->assertTrue($this->_file->getStringCalled);
        $this->assertTrue($this->_db->copyExistsForUrlCalled);
        $this->assertTrue($this->_db->addSiteUnknownPathCalled);
        $this->assertContains('ChiClassicComp', $this->_db->addSiteUnknownPathLastSiteNames);
        foreach ($paths as $path)
        {
            $this->assertContains($path, $this->_db->addSiteUnknownPathLastPaths);
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
            $existing['http://chiclassiccomp.org/docs/content/' . $path] = true;
        }
        $this->_db->copyExistsForUrlFakeResults = $existing;
    }

    private static function expectedOutputForPaths($paths, $idStart = 1, $sortById = true, $ascending = true)
    {
        $checks = array();
        foreach ($paths as $path)
        {
            $checks[] = '';
        }
        return self::expectedOutputForCheckedPaths($paths, $checks, $idStart, $sortById, $ascending);
    }

    private static function expectedOutputForCheckedPaths($paths, $checks, $idStart = 1, $sortById = true, $ascending = true)
    {
        if ($sortById)
        {
            $sortValue = $ascending ? 'byid' : 'byiddesc';
            $nextSortValue = $ascending ? 'byiddesc' : 'byid';
            $expectedIdHeader = sprintf('<a href="chiclassiccomp.php?sort=%1$s">Id</a>', $nextSortValue);
            $expectedPathHeader = '<a href="chiclassiccomp.php?sort=bypath">Path</a>';
        }
        else
        {
            $sortValue = $ascending ? 'bypath' : 'bypathdesc';
            $nextSortValue = $ascending ? 'bypathdesc' : 'bypath';
            $expectedIdHeader = '<a href="chiclassiccomp.php?sort=byid">Id</a>';
            $expectedPathHeader = sprintf('<a href="chiclassiccomp.php?sort=%1$s">Path</a>', $nextSortValue);
        }

        $expected = <<<EOH
<h1>New ChiClassicComp Publications</h1>

<div class="pagesel">Page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;</div>
<form action="chiclassiccomp.php" method="POST">
<input type="hidden" name="start" value="0" />
<input type="hidden" name="sort" value="$sortValue" />
<table>
<tr><th>$expectedIdHeader</th><th>$expectedPathHeader</th></tr>

EOH;
        $i = 0;
        $n = $idStart;
        foreach ($paths as $path)
        {
            $urlPath = ChiClassicCompPage::escapeSpecialChars($path);
            $checked = $checks[0];
            $checks = array_slice($checks, 1);
            $item = <<<EOH
<tr><td>$n.</td><td><input type="checkbox" id="ignore$i" name="ignore$i" value="$path" $checked/>
<a href="url-wizard.php?url=http://chiclassiccomp.org/docs/content/$urlPath">$path</a></td></tr>

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

    private function createPageWithoutFetchingIndexByDateFile($vars = array('sort' => SORT_ORDER_BY_ID))
    {
        $this->_db->getPropertyFakeResult = '10';
        $this->_info->expects($this->once())->method('lastModified')->willReturn('10');
        $this->_factory->expects($this->once())->method('createUrlInfo')
            ->with($this->equalTo(CCC_INDEX_BY_DATE_URL))->willReturn($this->_info);
        $this->createPage($vars);
    }

    private function createPage($vars = array())
    {
        $_SERVER['PATH_INFO'] = '';
        $this->_vars = $vars;
        $this->_page = new ChiClassicCompPageTester($this->_manx, $this->_vars, $this->_fileSystem, $this->_factory);
    }

    private function assertPropertyRead($name)
    {
        $this->assertTrue($this->_db->getPropertyCalled);
        $this->assertEquals($name, $this->_db->getPropertyLastName);
    }

    private function assertIndexByDateFileTransferred()
    {
        $this->assertTrue($this->_db->setPropertyCalled);
    }
}
