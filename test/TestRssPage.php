<?php

require_once 'pages/RssPage.php';
require_once 'test/FakeManxDatabase.php';
require_once 'pages/IDateTimeProvider.php';

class RssPageTester extends RssPage
{
    public function renderBody()
    {
        parent::renderBody();
    }
}

class FakeDateTimeProvider implements IDateTimeProvider
{
    public function now()
    {
        return $this->nowFakeResult;
    }
    public $nowFakeResult;
}

class TestRssPage extends PHPUnit\Framework\TestCase
{
    public function testRenderBody()
    {
        $_SERVER['PATH_INFO'] = '';
        $db = new FakeManxDatabase();
        $db->getMostRecentDocumentsFakeResult = array(
            array('ph_title' => 'The Foo Manual',
                'ph_company' => 5,
                'ph_pub' => 1211,
                'ph_abstract' => '',
                'ph_abstract' => '',
                'ph_created' => '24 Nov 1980 03:00 PM -0600',
                'company_name' => 'Digital Equipment Corporation',
                'company_short_name' => 'DEC',
                'ph_part' => '0123-XXY',
                'ph_revision' => '',
                'ph_pub_date' => '1979-03',
                'ph_keywords' => 'foo,bar')
        );
        $manx = $this->createMock(IManx::class);
        $manx->expects($this->once())->method('getDatabase')->willReturn($db);
        $dtp = new FakeDateTimeProvider();
        $dtp->nowFakeResult = new DateTime("03 Dec 1964 15:00:00 -0400");
        $page = new RssPageTester($manx, $dtp);

        $page->renderBody();

        $desc = htmlspecialchars(implode("\n", array(
            '<div style="margin: 10px"><p><strong style="color: #089698; background-color: transparent;">The Foo Manual</strong></p>',
            '<table><tbody><tr><td>Company:</td><td><a href="http://manx-docs.org/search.php?cp=5&q=">Digital Equipment Corporation</a></td></tr>',
            '<tr><td>Part:</td><td>0123-XXY</td></tr>',
            '<tr><td>Date:</td><td>1979-03</td></tr>',
            '<tr><td>Keywords:</td><td>foo,bar</td></tr>',
            '</tbody>',
            '</table>',
            '</div>'
        )) . "\n");
        $expected = array(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<rss version="2.0">',
            '  <channel>',
            '    <title>New Documents on Manx</title>',
            '    <link>http://manx-docs.org</link>',
            '    <description>A list of the most recently created documents in the Manx database.</description>',
            "    <lastBuildDate>Thu, 03 Dec 1964 15:00:00 -0400</lastBuildDate>",
            '    <language>en-us</language>',
            '    <item>',
            '      <title>The Foo Manual</title>',
            '      <link>http://manx-docs.org/details.php/5,1211</link>',
            '      <description>' . $desc . '</description>',
            '      <pubDate>Mon, 24 Nov 1980 15:00:00 -0600</pubDate>',
            '      <category>DEC</category>',
            '    </item>',
            '  </channel>',
            '</rss>'
        );
        $this->assertTrue($db->getMostRecentDocumentsCalled);
        $this->assertEquals(200, $db->getMostRecentDocumentsLastCount);
        $this->expectOutputString(implode("\n", $expected) . "\n");
    }
}
