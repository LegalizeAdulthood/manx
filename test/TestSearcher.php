<?php

require_once 'pages/Searcher.php';
require_once 'test/FakeFormatter.php';
require_once 'test/FakeManxDatabase.php';

class TestSearcher extends PHPUnit\Framework\TestCase
{
    public function testRenderCompanies()
    {
        $db = new FakeManxDatabase();
        $db->getCompanyListFakeResult = array(
            array('id' => 1, 'name' => 'DEC'),
            array('id' => 2, 'name' => '3Com'),
            array('id' => 3, 'name' => 'AT&T'));
        $searcher = Searcher::getInstance($db);
        ob_start();
        $searcher->renderCompanies(1);
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertTrue($db->getCompanyListCalled);
        $this->assertEquals('<select id="CP" name="cp">'
            . '<option value="1" selected="selected">DEC</option>'
            . '<option value="2">3Com</option>'
            . '<option value="3">AT&amp;T</option>'
            . '</select>', $output);
    }

    public function testParameterSourceHttpGet()
    {
        $get = array('cp' => 1);
        $post = array();
        $source = Searcher::parameterSource($get, $post);
        $this->assertEquals($get, $source);
        $this->assertTrue(array_key_exists('cp', $source));
    }

    public function testParameterSourceHttpPost()
    {
        $get = array();
        $post = array('cp' => 1);
        $source = Searcher::parameterSource($get, $post);
        $this->assertEquals($post, $source);
        $this->assertTrue(array_key_exists('cp', $source));
    }

    public function testParameterSourceDefaultGet()
    {
        $get = array('q' => 'terminal');
        $post = array();
        $source = Searcher::parameterSource($get, $post);
        $this->assertEquals($get, $source);
        $this->assertTrue(array_key_exists('q', $source));
    }

    public function testFilterSearchKeywordsIgnored()
    {
        $this->assertEquals(array(), Searcher::filterSearchKeywords("a an it on in at", $ignoredWords));
        $this->assertEquals(array('a', 'an', 'it', 'on', 'in', 'at'), $ignoredWords);
    }

    public function testFilterSearchKeywordsAcceptable()
    {
        $ignoredWords = array();
        $this->assertEquals(array('one', 'two'), Searcher::filterSearchKeywords("one two", $ignoredWords));
        $this->assertEquals(array(), $ignoredWords);
    }

    public function testSearchResultsSingleRow()
    {
        $db = new FakeManxDatabase();
        $rows = array(
            array('pub_id' => 1,
                'ph_part' => '',
                'ph_title' => '',
                'pub_has_online_copies' => '',
                'ph_abstract' => '',
                'pub_has_toc' => '',
                'pub_superseded' => '',
                'ph_pub_date' => '',
                'ph_revision' => '',
                'ph_company' => '',
                'ph_alt_part' => '',
                'ph_pub_type' => '')
            );
        $db->searchForPublicationsFakeResult = $rows;
        $db->getOSTagsForPubFakeResult = array('OpenVMS VAX Version 6.0');
        $formatter = new FakeFormatter();
        $searcher = Searcher::getInstance($db);
        $keywords = "graphics terminal";
        $company = 1;

        $searcher->renderSearchResults($formatter, $company, $keywords, true);
        $this->assertTrue($db->searchForPublicationsCalled);
        $this->assertEquals(1, $db->searchForPublicationsLastCompany);
        $this->assertEquals(explode(' ', $keywords), $db->searchForPublicationsLastKeywords);
        $this->assertTrue($db->searchForPublicationsLastOnline);
        $this->assertTrue($db->getOSTagsForPubCalled);
        $this->assertEquals(1, $db->getOSTagsForPubLastPubId);
        $this->assertTrue($formatter->renderResultsBarCalled);
        $this->assertEquals(array(), $formatter->renderResultsBarLastIgnoredWords);
        $this->assertEquals(array('graphics', 'terminal'), $formatter->renderResultsBarLastSearchWords);
        $this->assertEquals(0, $formatter->renderResultsBarLastStart);
        $this->assertEquals(0, $formatter->renderResultsBarLastEnd);
        $this->assertEquals(1, $formatter->renderResultsBarLastTotal);
        $this->assertTrue($formatter->renderPageSelectionBarCalled);
        $this->assertEquals(0, $formatter->renderPageSelectionBarLastStart);
        $this->assertEquals(1, $formatter->renderPageSelectionBarLastTotal);
        $this->assertEquals(10, $formatter->renderPageSelectionBarLastRowsPerPage);
        $this->assertTrue($formatter->renderResultsPageCalled);
        $rows[0]['tags'] = array('OpenVMS VAX Version 6.0');
        $this->assertEquals($rows, $formatter->renderResultsPageLastRows);
        $this->assertEquals(0, $formatter->renderResultsPageLastStart);
        $this->assertEquals(0, $formatter->renderResultsPageLastEnd);
        $this->assertEquals(2, $formatter->renderPageSelectionBarCallCount);
    }
}
