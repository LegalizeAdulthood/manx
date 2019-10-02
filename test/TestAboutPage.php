<?php

require_once 'pages/AboutPage.php';
require_once 'test/DatabaseTester.php';

class TestAboutPage extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $_SERVER['PATH_INFO'] = '';
        $this->_db = $this->createMock(IManxDatabase::class);
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->expects($this->any())->method('getDatabase')->willReturn($this->_db);
        $this->_page = new AboutPage($this->_manx);
    }

    public function testRenderDocumentSummary()
    {
        $this->_db->expects($this->once())->method('getDocumentCount')->willReturn(12);
        $this->_db->expects($this->once())->method('getOnlineDocumentCount')->willReturn(24);
        $this->_db->expects($this->once())->method('getSiteCount')->willReturn(43);

        $this->startOutputCapture();
        $this->_page->renderDocumentSummary();
        $output = $this->finishOutputCapture();

        $this->assertEquals("12 manuals, 24 of which are online, at 43 websites", $output);
    }

    public function testRenderCompanyList()
    {
        $this->_db->expects($this->once())->method('getCompanyList')->willReturn(
            array(
                array('id' => 1, 'name' => "DEC"),
                array('id' => 2, 'name' => "HP")
            ));

        $this->startOutputCapture();
        $this->_page->renderCompanyList();
        $output = $this->finishOutputCapture();

        $this->assertEquals('<a href="search.php?cp=1">DEC</a>, <a href="search.php?cp=2">HP</a>', $output);
    }

    public function testRenderSiteList()
    {
        $this->_db->expects($this->once())->method('getSiteList')->willReturn(
            DatabaseTester::createResultRowsForColumns(
                array('url', 'description', 'low'),
                array(
                    array('http://www.dec.com', 'DEC', false),
                    array('http://www.hp.com', 'HP', true)
                )
            )
        );

        $this->startOutputCapture();
        $this->_page->renderSiteList();
        $output = $this->finishOutputCapture();

        $this->assertEquals('<ul><li><a href="http://www.dec.com">DEC</a></li>'
            . '<li><a href="http://www.hp.com">HP</a> <span class="warning">(Low Bandwidth)</span></li></ul>', $output);
    }

    private function startOutputCapture()
    {
        ob_start();
    }

    private function finishOutputCapture()
    {
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    private $_db;
    private $_manx;
    private $_page;
}
