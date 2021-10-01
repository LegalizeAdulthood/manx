<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class DetailsPageStaticTest extends PHPUnit\Framework\TestCase
{
    public function testNeatListPlainOneItem()
    {
        $this->assertEquals('English', Manx\DetailsPage::neatListPlain(array('English')));
    }

    public function testNeatListPlainTwoItems()
    {
        $this->assertEquals('English and French', Manx\DetailsPage::neatListPlain(array('English', 'French')));
    }

    public function testNeatListPlainThreeItems()
    {
        $this->assertEquals('English, French and German', Manx\DetailsPage::neatListPlain(array('English', 'French', 'German')));
    }

    public function testDetailParamsForPathInfoCompanyAndId()
    {
        $params = Manx\DetailsPage::detailParamsForPathInfo('/1,2');

        $this->assertEquals(4, count(array_keys($params)));
        $this->assertEquals(1, $params['cp']);
        $this->assertEquals(2, $params['id']);
        $this->assertEquals(1, $params['cn']);
        $this->assertEquals(0, $params['pn']);
    }

    public function testFormatDocRefNoPart()
    {
        $row = array('ph_company' => 1, 'ph_pub' => 3, 'ph_title' => 'Frobozz Electric Company Grid Adjustor & Pulminator Reference', 'ph_part' => NULL);

        $result = Manx\DetailsPage::formatDocRef($row);

        $this->assertEquals('<a href="../details.php/1,3"><cite>Frobozz Electric Company Grid Adjustor &amp; Pulminator Reference</cite></a>', $result);
    }

    public function testFormatDocRefWithPart()
    {
        $row = array('ph_company' => 1, 'ph_pub' => 3, 'ph_title' => 'Frobozz Electric Company Grid Adjustor & Pulminator Reference', 'ph_part' => 'FECGAPR');

        $result = Manx\DetailsPage::formatDocRef($row);

        $this->assertEquals('FECGAPR, <a href="../details.php/1,3"><cite>Frobozz Electric Company Grid Adjustor &amp; Pulminator Reference</cite></a>', $result);
    }

    public function testReplaceNullWithEmptyStringOrTrimForNull()
    {
        $this->assertEquals('', Manx\DetailsPage::replaceNullWithEmptyStringOrTrim(null));
    }

    public function testReplaceNullWithEmptyStringOrTrimForString()
    {
        $this->assertEquals('foo', Manx\DetailsPage::replaceNullWithEmptyStringOrTrim('foo'));
    }

    public function testReplaceNullWithEmptyStringOrTrimForWhitespace()
    {
        $this->assertEquals('foo', Manx\DetailsPage::replaceNullWithEmptyStringOrTrim(" foo\t\r\n"));
    }
}
