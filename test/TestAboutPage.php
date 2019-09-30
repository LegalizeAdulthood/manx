<?php

require_once 'pages/AboutPage.php';
require_once 'test/FakeManxDatabase.php';

class TestAboutPage extends PHPUnit\Framework\TestCase
{
    private $_db;
    private $_manx;
    private $_page;

    private function createInstance()
    {
        $_SERVER['PATH_INFO'] = '';
        $this->_db = new FakeManxDatabase();
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->expects($this->once())->method('getDatabase')->willReturn($this->_db);
        $this->_page = new AboutPage($this->_manx);
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

    public function testRenderDocumentSummary()
    {
        $this->createInstance();
        $this->_db->getDocumentCountFakeResult = 12;
        $this->_db->getOnlineDocumentCountFakeResult = 24;
        $this->_db->getSiteCountFakeResult = 43;
        $this->startOutputCapture();
        $this->_page->renderDocumentSummary();
        $output = $this->finishOutputCapture();
        $this->assertTrue($this->_db->getDocumentCountCalled);
        $this->assertTrue($this->_db->getOnlineDocumentCountCalled);
        $this->assertTrue($this->_db->getSiteCountCalled);
        $this->assertEquals("12 manuals, 24 of which are online, at 43 websites", $output);
    }

    public function testRenderCompanyList()
    {
        $this->createInstance();
        $this->_db->getCompanyListFakeResult = array(
            array('id' => 1, 'name' => "DEC"),
            array('id' => 2, 'name' => "HP"));
        $this->startOutputCapture();
        $this->_page->renderCompanyList();
        $output = $this->finishOutputCapture();
        $this->assertTrue($this->_db->getCompanyListCalled);
        $this->assertEquals('<a href="search.php?cp=1">DEC</a>, <a href="search.php?cp=2">HP</a>', $output);
    }

    public function testRenderSiteList()
    {
        $this->createInstance();
        $this->_db->getSiteListFakeResult = FakeDatabase::createResultRowsForColumns(
            array('url', 'description', 'low'),
            array(
                array('http://www.dec.com', 'DEC', false),
                array('http://www.hp.com', 'HP', true)
            ));
        $this->startOutputCapture();
        $this->_page->renderSiteList();
        $output = $this->finishOutputCapture();
        $this->assertEquals('<ul><li><a href="http://www.dec.com">DEC</a></li>'
            . '<li><a href="http://www.hp.com">HP</a> <span class="warning">(Low Bandwidth)</span></li></ul>', $output);
    }
}
