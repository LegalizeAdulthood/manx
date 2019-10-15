<?php

require_once 'pages/PublicationPage.php';
require_once 'test/DatabaseTester.php';
require_once 'test/FakeFile.php';

class PublicationPageTester extends PublicationPage
{
    public function __construct($manx, $vars)
    {
        $this->redirectCalled = false;
        parent::__construct($manx, $vars);
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

class TestPublicationPage extends PHPUnit\Framework\TestCase
{
    /** @var array */
    private $_vars;
    /** @var IManxDatabase */
    private $_db;
    /** @var IManx */
    private $_manx;
    /** @var PublicationPageTester */
    private $_page;
    /** @var IUser */
    private $_user;

    protected function setUp()
    {
        $this->_db = $this->createMock(IManxDatabase::class);
        $this->_manx = $this->createMock(IManx::class);
        $this->_manx->method('getDatabase')->willReturn($this->_db);
        $this->_user = $this->createMock(IUser::class);
        $this->_manx->expects($this->once())->method('getUserFromSession')->willReturn($this->_user);
    }

    function createPage($vars = array())
    {
        $_SERVER['PATH_INFO'] = '';
        $this->_vars = $vars;
        $this->_page = new PublicationPageTester($this->_manx, $this->_vars);
    }

    public function testMenuTypeIsPublicationPage()
    {
        $this->createPage();

        $this->assertEquals(MenuType::Publication, $this->_page->getMenuType());
    }

    function testRenderAddPublicationForm()
    {
        $this->_db->expects($this->once())->method('getCompanyList')->willReturn(array(array('id' => 3, 'name' => 'bitsavers')));
        $this->createPage(array('id' => -1));
        $output = <<<EOH
<h1>Add Publication</h1>

<div id="addformdiv"><form id="addform" action="publication.php" method="POST" name="f">
<fieldset><legend id="plum">Essentials</legend><ul>
<li><label for="company">Company:</label><select id="company" name="company"><option value="3">bitsavers</option>
</select>

<li><label for="part">Part or order no.:</label>
<input type="text" id="part" name="part" value="">
<button id="lkpt">Lookup</button>
<div id="partlist"></div>
</li>
<li><label for="pubdate">Publication date:</label>
<input type="text" id="pubdate" name="pubdate" value="" size="10" maxlength="10"></li>
<li><label for="title">Title:</label>
<input type="text" id="title" name="title" value="" size="40"></li>
</ul></fieldset>

<fieldset><legend>Other bits</legend><ul>
<li><label for="pt">Publication type:</label>
<select id="pt" name="pt">
<option value="D">document</option>
<option value="A">addendum</option>'
</select></li>

<li><label for="altpart">Alternative part no.:</label>
<input type="text" id="altpart" name="altpart" value=""></li>
<li><label for="revision">Revision:</label>
<input type="text" id="revision" name="revision" value=""></li>
<li><label for="keywords">Keywords:</label>
<input type="text" id="keywords" name="keywords" value=""></li>
<li><label for="notes">Notes:</label>
<input type="text" id="notes" name="notes" value=""></li>
<li><label for="lang">Language(s):</label>
<input type="text" id="lang" name="lang" value=""></li>
</ul></fieldset>

<input type="submit" name="opsave" value="Save">
</form></div>

EOH;

        $this->_page->renderBodyContent();

        $this->expectOutputString($output);
    }

    function testAddPublication()
    {
        $company = 15;
        $part = 'XX-AAB';
        $pubDate = '1979-11-12';
        $title = 'title';
        $publicationType = 'D';
        $altPart = 'YY-BBA';
        $revision = 'Rev. A';
        $keywords = 'foo bar';
        $notes = 'notes';
        $abstract = 'abstract';
        $languages = 'en';
        $this->createPage(array(
            "company" => $company, "part" => $part, "pubdate" => $pubDate,
            "title" => $title, "pt" => $publicationType, "altpart" => $altPart,
            "revision" => $revision, "keywords" => $keywords, "notes" => $notes,
            "abstract" => $abstract, "lang" => $languages
        ));
        $this->_manx->expects($this->once())->method('addPublication')
            ->with($this->_user, $company, $part, $pubDate, $title, $publicationType,
                $altPart, $revision, $keywords, $notes, $abstract, $languages)
            ->willReturn(11);

        $this->_page->postPage();
    }
}
