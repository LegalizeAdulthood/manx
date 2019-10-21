<?php

use Pimple\Container;

require_once 'pages/CompanyPage.php';
require_once 'test/DatabaseTester.php';
require_once 'test/FakeFile.php';

class CompanyPageTester extends CompanyPage
{
    public function __construct($config)
    {
        $this->redirectCalled = false;
        parent::__construct($config);
    }

    public function getMenuType()
    {
        return parent::getMenuType();
    }

    public function renderBodyContent()
    {
        parent::renderBodyContent();
    }
    
    public function postPage()
    {
        parent::postPage();
    }

    public function redirect($url)
    {
        $this->redirectCalled = true;
        $this->redirectLastUrl = $url;
    }
    public $redirectCalled, $redirectLastUrl;
}

class TestCompanyPage extends PHPUnit\Framework\TestCase
{
    /** @var Container */
    private $_config;
    /** @var IManxDatabase */
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
    /** @var CompanyPageTester */
    private $_page;
    /** @var FakeFile */
    private $_file;

    protected function setUp()
    {
        $this->_db = $this->createMock(IManxDatabase::class);
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->method('getDatabase')->willReturn($this->_db);
        $this->_fileSystem = $this->createMock(IFileSystem::class);
        $this->_factory = $this->createMock(IWhatsNewPageFactory::class);
        $this->_info = $this->createMock(IUrlInfo::class);
        $this->_transfer = $this->createMock(IUrlTransfer::class);
        $this->_file = new FakeFile();
        $config = new Container();
        $config['manx'] = $this->_manx;
        $this->_config = $config;
    }

    function createPage($vars = array())
    {
        $_SERVER['PATH_INFO'] = '';
        $this->_config['vars'] = $vars;
        $this->_page = new CompanyPageTester($this->_config, $this->_fileSystem, $this->_factory);
    }

    public function testMenuTypeIsCompanyPage()
    {
        $this->createPage();

        $this->assertEquals(MenuType::Company, $this->_page->getMenuType());
    }

    public function testRenderSelectCompany()
    {
        $this->createPage();
        $this->_db->expects($this->once())->method('getCompanyList')->willReturn(array(array('id' => 3, 'name' => 'bitsavers')));
        $output = <<<EOH
<h1>Edit Company</h1>

<div id="compedit">
<form action="company.php" id="editform" method="get">
<fieldset><legend>Edit Company</legend>
<ul>
<li><label for="id">Full name</label>
<select id="id" name="id">
<option value="-1">(New Company)</option>
<option value="3">bitsavers</option>
</select>
</li>
</ul>
</fieldset>
<input type="submit" name="opedit" value="Edit" />
</form>
</div>

EOH;

        $this->_page->renderBodyContent();

        $this->expectOutputString($output);
    }

    function testRenderAddCompanyForm()
    {
        $this->createPage(array('id' => -1));
        $output = <<<EOH
<h1>Add Company</h1>

<div id="compedit">
<form action="company.php" id="editform" method="post">
<fieldset><legend>Add Company</legend>
<ul>
<li><label for="coname">Full name</label>
<input type="text" name="coname" value="" size="40" maxlength="50" /></li>
<li><label for="coshort">Short name or abbrev.</label>
<input type="text" name="coshort" value="" size="40" maxlength="50" /></li>
<li><label for="cosort">Name for sorting purposes</label>
<input type="text" name="cosort" value="" size="40" maxlength="50" /></li>
<li><label for="display">Displayed?</label>
<input type="checkbox" name="display" checked="checked" value="Y"/></li>
<li><label for="notes">Notes</label>
<input type="text" name="notes" value="" size="40" maxlength="255" /></li>
</ul>
</fieldset>
<input type="hidden" name="id" value="-1" />
<input type="submit" name="opsave" value="Save" />
</form>
</div>

EOH;

        $this->_page->renderBodyContent();

        $this->expectOutputString($output);
    }

    function testAddCompany()
    {
        $name = 'Digital Equipment Corporation';
        $this->createPage(array('id' => -1,
            'coname' => $name,
            'coshort' => 'DEC',
            'cosort' => 'dec',
            'display' => 'Y',
            'notes' => 'notes',
            'opsave' => 'Save'
        ));
        $this->_db->expects($this->once())->method('addCompany')->with('Digital Equipment Corporation', 'DEC', 'dec', true, 'notes');

        $this->_page->postPage();
    }

    function testEditCompany()
    {
        $name = 'Digital Equipment Corporation';
        $id = 66;
        $shortName = 'DEC';
        $sortName = 'dec';
        $display = true;
        $notes = 'some notes';
        $this->createPage(array('id' => $id,
            'coname' => $name,
            'coshort' => $shortName,
            'cosort' => $sortName,
            'display' => $display ? 'Y' : '',
            'notes' => $notes,
            'opsave' => 'Save'
        ));
        $this->_db->expects($this->once())->method('updateCompany')->with($id, $name, $shortName, $sortName, $display, $notes);

        $this->_page->postPage();
    }
}
