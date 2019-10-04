<?php

require_once 'pages/Searcher.php';

class TestSearcher extends PHPUnit\Framework\TestCase
{
    public function testRenderCompanies()
    {
        $db = $this->createMock(IManxDatabase::class);
        $db->expects($this->once())->method('getCompanyList')->willReturn(array(
            array('id' => 1, 'name' => 'DEC'),
            array('id' => 2, 'name' => '3Com'),
            array('id' => 3, 'name' => 'AT&T')));
        $searcher = Searcher::getInstance($db);

        $searcher->renderCompanies(1);

        $this->expectOutputString('<select id="CP" name="cp">'
            . '<option value="1" selected="selected">DEC</option>'
            . '<option value="2">3Com</option>'
            . '<option value="3">AT&amp;T</option>'
            . '</select>');
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
        $db = $this->createMock(IManxDatabase::class);
        $pubId = 4344;
        $rows = array(
            array('pub_id' => $pubId,
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
        $keywords = "graphics terminal";
        $company = 1;
        $db->expects($this->once())->method('searchForPublications')
            ->with($company, explode(' ', $keywords), true)
            ->willReturn($rows);
        $tags = array('OpenVMS VAX Version 6.0');
        $db->expects($this->once())->method('getOSTagsForPub')
            ->with($pubId)
            ->willReturn($tags);
        $formatter = $this->createMock(IFormatter::class);
        $formatter->expects($this->once())->method('renderResultsBar')
            ->with(array(), array('graphics', 'terminal'), 0, 0, 1);
        $formatter->expects($this->exactly(2))->method('renderPageSelectionBar')
            ->with(0, 1, 10);
        $rowArgs = $rows;
        $rowArgs[0]['tags'] = $tags;
        $formatter->expects($this->once())->method('renderResultsPage')
            ->with($rowArgs, 0, 0);
        $searcher = Searcher::getInstance($db);

        $searcher->renderSearchResults($formatter, $company, $keywords, true);
    }
}
