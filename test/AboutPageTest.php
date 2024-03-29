<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class AboutPageTest extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $_SERVER['PATH_INFO'] = '';
        $this->_db = $this->createMock(Manx\IManxDatabase::class);
        $this->_manx = $this->createMock(Manx\IManx::class);
        $this->_manx->expects($this->any())->method('getDatabase')->willReturn($this->_db);
        $config = new Container();
        $config['manx'] = $this->_manx;
        $this->_page = new Manx\AboutPage($config);
    }

    public function testRenderDocumentSummary()
    {
        $this->_db->expects($this->once())->method('getDocumentCount')->willReturn(12);
        $this->_db->expects($this->once())->method('getOnlineDocumentCount')->willReturn(24);
        $this->_db->expects($this->once())->method('getSiteCount')->willReturn(43);

        $this->_page->renderDocumentSummary();

        $this->expectOutputString("12 manuals, 24 of which are online, at 43 websites");
    }

    public function testRenderCompanyList()
    {
        $this->_db->expects($this->once())->method('getCompanyList')->willReturn(
            array(
                array('id' => 1, 'name' => "DEC"),
                array('id' => 2, 'name' => "HP")
            ));

        $this->_page->renderCompanyList();

        $this->expectOutputString('<a href="search.php?cp=1">DEC</a>, <a href="search.php?cp=2">HP</a>');
    }

    public function testRenderSiteList()
    {
        $this->_db->expects($this->once())->method('getSiteList')->willReturn(
            \Manx\Test\RowFactory::createResultRowsForColumns(
                array('url', 'description', 'low'),
                array(
                    array('http://www.dec.com', 'DEC', false),
                    array('http://www.hp.com', 'HP', true)
                )
            )
        );

        $this->_page->renderSiteList();

        $this->expectOutputString('<ul><li><a href="http://www.dec.com">DEC</a></li>'
            . '<li><a href="http://www.hp.com">HP</a> <span class="warning">(Low Bandwidth)</span></li></ul>');
    }

    private $_db;
    private $_manx;
    private $_page;
}
