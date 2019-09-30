<?php

require_once 'test/FakeManxDatabase.php';
require_once 'test/UrlWizardServiceTester.php';

class TestUrlWizardService extends PHPUnit\Framework\TestCase
{
    public function testUrlComponentsMatchBitSaversOrg()
    {
        $this->assertUrlMatchesSite(
            'http://bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf',
            'http://bitsavers.org/pdf/');
    }

    public function testUrlComponentsMatchWwwBitSaversOrg()
    {
        $this->assertUrlMatchesSite(
            'http://www.bitsavers.org/pdf/univac/1100/UE-637_1108execUG_1970.pdf',
            'http://bitsavers.org/pdf/');
    }

    private function assertUrlMatchesSite($url, $site)
    {
        $this->assertTrue(UrlWizardService::urlComponentsMatch(parse_url($url), parse_url($site)));
    }

    public function testExtractPubDateSingleTrailingDigit()
    {
        list($date, $newFileBase) = UrlWizardService::extractPubDate('foo_bar_3');
        $this->assertEquals('', $date);
        $this->assertEquals('foo_bar_3', $newFileBase);
    }

    public function testExtractPubDateSeparateMonthYear()
    {
        $this->assertPubDateForFileBase('1975-03', 'foo_bar_Mar_1975');
    }

    public function testExtractPubDateMonthYear()
    {
        $this->assertPubDateForFileBase('1975-03', 'foo_bar_Mar1975');
    }

    public function testExtractPubDateYear()
    {
        $this->assertPubDateForFileBase('1975', 'foo_bar_1975');
    }

    public function testExtractPubDateTwoDigitYear()
    {
        $this->assertPubDateForFileBase('1975', 'foo_bar_75');
    }

    public function testExtractPubDateSeparateMonthTwoDigitYear()
    {
        $this->assertPubDateForFileBase('1975-03', 'foo_bar_Mar_75');
    }

    public function testExtractPubDateMonthTwoDigitYear()
    {
        $this->assertPubDateForFileBase('1975-03', 'foo_bar_Mar75');
    }

    private function assertPubDateForFileBase($pubDate, $fileBase)
    {
        list($date, $newFileBase) = UrlWizardService::extractPubDate($fileBase);
        $this->assertEquals($pubDate, $date);
        $this->assertEquals('foo_bar', $newFileBase);
    }

    public function testConstruct()
    {
        $this->_manx = $this->createMock(IManx::class);
        $_SERVER['PATH_INFO'] = '';
        $vars = array();
        $page = new UrlWizardServiceTester($this->_manx, $vars);
        $this->assertTrue(is_object($page));
        $this->assertFalse(is_null($page));
    }

    public function testComparePublicationsByTitle()
    {
        list($left, $right) = $this->createPublicationsForCompare('', '', 'foo', '', '', 'bar');
        $this->assertEquals(1, UrlWizardService::comparePublications($left, $right));
    }

    public function testComparePublicationsByPart()
    {
        list($left, $right) = $this->createPublicationsForCompare('00', '', 'foo', '01', '', 'bar');
        $this->assertEquals(-1, UrlWizardService::comparePublications($left, $right));
    }

    public function testComparePublicationsByRev()
    {
        list($left, $right) = $this->createPublicationsForCompare('00', 'B', 'foo', '00', 'A', 'foo');
        $this->assertEquals(1, UrlWizardService::comparePublications($left, $right));
    }

    public function testTitleForBaseWithPoundSign()
    {
        $this->assertTitleForFileBase('Micro Cornucopia #50', 'Micro_Cornucopia_%2350');
    }

    public function testTitleForFileBaseWithUnderscores()
    {
        $this->assertTitleForFileBase('Foo Bar Gronky', 'Foo_Bar_Gronky');
    }

    public function testTitleForFileBaseWithSpaces()
    {
        $this->assertTitleForFileBase('Foo Bar Gronky', 'Foo_Bar Gronky');
    }

    public function testTitleForFileBaseWithMixedCase()
    {
        $this->assertTitleForFileBase('Foo Bar Gronky', 'FooBarGronky');
    }

    public function testTitleMixedCaseAndUnderscores()
    {
        $this->assertTitleForFileBase('TI CBL Real World Math Guidebook', 'TI_CBL_RealWorldMath_Guidebook');
    }

    public function testTitleForFileBaseWithMixedCaseTwoWords()
    {
        $this->assertTitleForFileBase('Foo Bar', 'FooBar');
    }

    public function testTitleForFileBaseWithMixedCaseFourWords()
    {
        $this->assertTitleForFileBase('Foo Bar Blobby Phlegm', 'FooBarBlobbyPhlegm');
    }

    private function assertTitleForFileBase($title, $fileBase)
    {
        $this->assertEquals($title, UrlWizardService::titleForFileBase($fileBase));
    }

    private function createPublicationsForCompare($leftPart, $leftRev, $leftTitle, $rightPart, $rightRev, $rightTitle)
    {
        $columns = array('ph_pub', 'ph_part', 'ph_revision', 'ph_title');
        $left = FakeDatabase::createResultRowsForColumns($columns,
            array(array('1', $leftPart, $leftRev, $leftTitle)));
        $left = $left[0];
        $right = FakeDatabase::createResultRowsForColumns($columns,
            array(array('2', $rightPart, $rightRev, $rightTitle)));
        $right = $right[0];
        return array($left, $right);
    }
}
