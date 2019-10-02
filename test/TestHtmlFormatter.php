<?php
require_once 'test/DatabaseTester.php';
require_once 'pages/HtmlFormatter.php';

class TestHtmlFormatter extends PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $formatter = HtmlFormatter::getInstance();
        $this->assertTrue(is_object($formatter));
        $this->assertFalse(is_null($formatter));
    }

    public function testRenderResultsBarAllDocumentsOneResult()
    {
        $formatter = HtmlFormatter::getInstance();

        $formatter->renderResultsBar(array(), array(), 0, 0, 1);

        $this->expectOutputString('<div class="resbar">Showing all documents. Results <b>1 - 1</b> of <b>1</b>.</div>');
    }

    public function testNeatQuotedListOneWord()
    {
        $this->assertEquals('"graphics"', HtmlFormatter::neatQuotedList(array('graphics')));
    }

    public function testNeatQuotedListTwoWords()
    {
        $this->assertEquals('"graphics" and "terminal"', HtmlFormatter::neatQuotedList(array('graphics', 'terminal')));
    }

    public function testNeatQuotedListThreeWords()
    {
        $this->assertEquals('"graphics", "terminal" and "serial"',
            HtmlFormatter::neatQuotedList(array('graphics', 'terminal', 'serial')));
    }

    public function testRenderResultsBarSearchWordsOneResult()
    {
        $formatter = HtmlFormatter::getInstance();

        $formatter->renderResultsBar(array(), array('graphics', 'terminal'), 0, 0, 1);

        $this->expectOutputString('<div class="resbar">Searching for "graphics" and "terminal". Results <b>1 - 1</b> of <b>1</b>.</div>');
    }

    public function testRenderResultsBarIgnoredWordsOneResult()
    {
        $formatter = HtmlFormatter::getInstance();

        $formatter->renderResultsBar(array('a', 'an', 'it'), array('graphics', 'terminal'), 0, 0, 1);

        $this->expectOutputString('<p class="warning">Ignoring "a", "an" and "it".  All search words must be at least three letters long.</p>'
            . '<div class="resbar">Searching for "graphics" and "terminal". Results <b>1 - 1</b> of <b>1</b>.</div>');
    }

    public function testRenderPageSelectionBarOnePage()
    {
        $formatter = HtmlFormatter::getInstance();

        $formatter->renderPageSelectionBar(0, 4, 10, array());

        $this->expectOutputString('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;</div>');
    }

    public function testRenderPageSelectionBarPageOneOfTwo()
    {
        $formatter = HtmlFormatter::getInstance();

        $formatter->renderPageSelectionBar(0, 19, 10, array('q' => 'vt220 terminal', 'cp' => 1));

        $this->expectOutputString('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
            . '<a class="navpage" href="search.php?q=vt220+terminal&start=10&cp=1">2</a>&nbsp;&nbsp;'
            . '<a href="search.php?q=vt220+terminal&start=10&cp=1"><b>Next</b></a>'
            . '</div>');
    }

    public function testRenderPageSelectionBarPageTwoOfThree()
    {
        $formatter = HtmlFormatter::getInstance();

        $formatter->renderPageSelectionBar(10, 29, 10, array('q' => 'vt220 terminal', 'cp' => 1, 'start' => 10));

        $this->expectOutputString('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;'
            . '<a href="search.php?q=vt220+terminal&start=0&cp=1"><b>Previous</b></a>&nbsp;&nbsp;'
            . '<a class="navpage" href="search.php?q=vt220+terminal&start=0&cp=1">1</a>&nbsp;&nbsp;'
            . '<b class="currpage">2</b>&nbsp;&nbsp;'
            . '<a class="navpage" href="search.php?q=vt220+terminal&start=20&cp=1">3</a>&nbsp;&nbsp;'
            . '<a href="search.php?q=vt220+terminal&start=20&cp=1"><b>Next</b></a></div>');
    }

    public function testRenderPageSelectionBarPageThreeOfThree()
    {
        $formatter = HtmlFormatter::getInstance();

        $formatter->renderPageSelectionBar(20, 29, 10, array('q' => 'vt100 terminal', 'cp' => 1, 'start' => 20));

        $this->expectOutputString('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;'
            . '<a href="search.php?q=vt100+terminal&start=10&cp=1"><b>Previous</b></a>&nbsp;&nbsp;'
            . '<a class="navpage" href="search.php?q=vt100+terminal&start=0&cp=1">1</a>&nbsp;&nbsp;'
            . '<a class="navpage" href="search.php?q=vt100+terminal&start=10&cp=1">2</a>&nbsp;&nbsp;'
            . '<b class="currpage">3</b>&nbsp;&nbsp;</div>');
    }

    public function testRenderPageSelectionBarPageOneOfTwoOnline()
    {
        $formatter = HtmlFormatter::getInstance();

        $formatter->renderPageSelectionBar(0, 19, 10, array('q' => 'vt220 terminal', 'cp' => 1, 'on' => 'on'));

        $this->expectOutputString('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
            . '<a class="navpage" href="search.php?q=vt220+terminal&start=10&on=on&cp=1">2</a>&nbsp;&nbsp;'
            . '<a href="search.php?q=vt220+terminal&start=10&on=on&cp=1"><b>Next</b></a>'
            . '</div>');
    }

    public function testRenderPageSelectionBarPageOneOfTwoFivePerPage()
    {
        $formatter = HtmlFormatter::getInstance();

        $formatter->renderPageSelectionBar(0, 9, 5, array('q' => 'vt220 terminal', 'cp' => 1, 'on' => 'on', 'num' => 5));

        $this->expectOutputString('<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;<b class="currpage">1</b>&nbsp;&nbsp;'
            . '<a class="navpage" href="search.php?q=vt220+terminal&start=5&num=5&on=on&cp=1">2</a>&nbsp;&nbsp;'
            . '<a href="search.php?q=vt220+terminal&start=5&num=5&on=on&cp=1"><b>Next</b></a>'
            . '</div>');
    }

    public function testRenderResultsPage()
    {
        $formatter = HtmlFormatter::getInstance();
        $rows = DatabaseTester::createResultRowsForColumns(
            array('pub_id', 'ph_part', 'ph_title', 'pub_has_online_copies',
                'ph_abstract', 'pub_has_toc', 'pub_superseded', 'ph_pub_date',
                'ph_revision', 'ph_company', 'ph_alt_part', 'ph_pub_type', 'tags'),
            array(
                array(1, 'AA-4949A-TC', 'VT55 Programming Manual', 1, NULL, 1, 0, '1977-02', '', 1, NULL, 'D', array()),
                array(3014, 'EK-VT55E-TM-001', "VT55-D,E,H,J DECgraphic Scope Users' Manual", 1, NULL, 1, 0, '1976-12', '', 1, NULL, 'D', array()),
                array(9206, 'MP-00098-00', 'VT55 Field Maintenance Print Set', 0, NULL, 0, 0, NULL, '', 1, NULL, 'D', array())
                ));

        $formatter->renderResultsPage($rows, 0, 2);

        $this->expectOutputString('<table class="restable">'
            . '<thead>'
                . '<tr><th>Part</th><th>Date</th><th>Title</th><th class="last">Status</th></tr>'
            . '</thead>'
            . '<tbody>'
            . '<tr valign="top">'
                . '<td>AA-4949A-TC</td>'
                . '<td>1977-02</td>'
                . '<td><a href="details.php/1,1">VT55 Programming Manual</a></td>'
                . '<td>Online, ToC</td>'
            . '</tr>'
            . '<tr valign="top">'
                . '<td>EK-VT55E-TM-001</td><td>1976-12</td>'
                . '<td><a href="details.php/1,3014">VT55-D,E,H,J DECgraphic Scope Users\' Manual</a></td>'
                . '<td>Online, ToC</td>'
            . '</tr>'
            . '<tr valign="top">'
                . '<td>MP-00098-00</td>'
                . '<td></td>'
                . '<td><a href="details.php/1,9206">VT55 Field Maintenance Print Set</a></td>'
                . '<td>&nbsp;</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>');
    }
}
