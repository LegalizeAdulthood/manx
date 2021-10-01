<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

class TestUrlMetaDataHelpers extends PHPUnit\Framework\TestCase
{
    public function testExtractPartNumberLeadingDigitsSpaceSeparators()
    {
        list($partNumber, $fileBase) = Manx\UrlMetaData::extractPartNumber('800-1023-01 - Adaptec ACB 4000 and 5000 Series Disk Controllers OEM Manual (Preliminary)');

        $this->assertEquals('800-1023-01', $partNumber);
        $this->assertEquals('- Adaptec ACB 4000 and 5000 Series Disk Controllers OEM Manual (Preliminary)', $fileBase);
    }

    public function testExtractPartNumberLeadingDigitsUnderscoreSeparators()
    {
        list($partNumber, $fileBase) = Manx\UrlMetaData::extractPartNumber('800-1023-01_Adaptec_ACB_4000_and_5000_Series_Disk_Controllers_OEM_Manual_(Preliminary)');

        $this->assertEquals('800-1023-01', $partNumber);
        $this->assertEquals('Adaptec_ACB_4000_and_5000_Series_Disk_Controllers_OEM_Manual_(Preliminary)', $fileBase);
    }

    public function testExtractFileNameExtensionWithExtension()
    {
        list($fileName, $fileBase, $extension) = Manx\UrlMetaData::extractFileNameExtension('foo.bar.pdf');

        $this->assertEquals('foo.bar.pdf', $fileName);
        $this->assertEquals('foo.bar', $fileBase);
        $this->assertEquals('pdf', $extension);
    }

    public function testExtractFileNameExtensionNoExtension()
    {
        list($fileName, $fileBase, $extension) = Manx\UrlMetaData::extractFileNameExtension('foo_bar');

        $this->assertEquals('foo_bar', $fileName);
        $this->assertEquals('foo_bar', $fileBase);
        $this->assertEquals('', $extension);
    }

    public function testExtractFileNameExtensionEncodedChars()
    {
        list($fileName, $fileBase, $extension) = Manx\UrlMetaData::extractFileNameExtension('foo%20bar%2Epdf');

        $this->assertEquals('foo bar.pdf', $fileName);
        $this->assertEquals('foo bar', $fileBase);
        $this->assertEquals('pdf', $extension);
    }

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
        $this->assertTrue(Manx\UrlMetaData::urlComponentsMatch(parse_url($url), parse_url($site)));
    }

    public function testExtractPubDateNoSeparatorSuffixTwoDigitYear()
    {
        list($pubDate, $newFileBase) = Manx\UrlMetaData::extractPubDate('foobarJun85');

        $this->assertEquals('1985-06', $pubDate);
        $this->assertEquals('foobar', $newFileBase);
    }

    public function testExtractPubDateNoSeparatorSuffixDayTwoDigitYear()
    {
        list($pubDate, $newFileBase) = Manx\UrlMetaData::extractPubDate('foobar10Jun85');

        $this->assertEquals('1985-06-10', $pubDate);
        $this->assertEquals('foobar', $newFileBase);
    }

    public function testExtractPubDateNoSeparatorSuffixFourDigitYear()
    {
        list($pubDate, $newFileBase) = Manx\UrlMetaData::extractPubDate('foobarJun1985');

        $this->assertEquals('1985-06', $pubDate);
        $this->assertEquals('foobar', $newFileBase);
    }

    public function testExtractPubDateNoSeparatorSuffixDayFourDigitYear()
    {
        list($pubDate, $newFileBase) = Manx\UrlMetaData::extractPubDate('foobar10Jun1985');

        $this->assertEquals('1985-06-10', $pubDate);
        $this->assertEquals('foobar', $newFileBase);
    }

    public function testExtractPubDateNoSeparatorSuffixFullMonthTwoDigitYear()
    {
        list($pubDate, $newFileBase) = Manx\UrlMetaData::extractPubDate('foobarOctober85');

        $this->assertEquals('1985-10', $pubDate);
        $this->assertEquals('foobar', $newFileBase);
    }

    public function testExtractPubDateNoSeparatorSuffixDayFullMonthTwoDigitYear()
    {
        list($pubDate, $newFileBase) = Manx\UrlMetaData::extractPubDate('foobar7October85');

        $this->assertEquals('1985-10-07', $pubDate);
        $this->assertEquals('foobar', $newFileBase);
    }

    public function testExtractPubDateNoSeparatorSuffixFullMonthFourDigitYear()
    {
        list($pubDate, $newFileBase) = Manx\UrlMetaData::extractPubDate('foobarOctober1985');

        $this->assertEquals('1985-10', $pubDate);
        $this->assertEquals('foobar', $newFileBase);
    }

    public function testExtractPubDateNoSeparatorSuffixDayFullMonthFourDigitYear()
    {
        list($pubDate, $newFileBase) = Manx\UrlMetaData::extractPubDate('foobar07October1985');

        $this->assertEquals('1985-10-07', $pubDate);
        $this->assertEquals('foobar', $newFileBase);
    }

    public function testExtractPubDateSpaceSeparator()
    {
        list($pubDate, $newFileBase) = Manx\UrlMetaData::extractPubDate('foo bar March 15 1975');

        $this->assertEquals('1975-03-15', $pubDate);
        $this->assertEquals('foo bar', $newFileBase);
    }

    public function testExtractPubDateSingleTrailingDigit()
    {
        list($date, $newFileBase) = Manx\UrlMetaData::extractPubDate('foo_bar_3');

        $this->assertEquals('', $date);
        $this->assertEquals('foo_bar_3', $newFileBase);
    }

    public function testExtractPubDateBogusNumber()
    {
        list($date, $newFileBase) = Manx\UrlMetaData::extractPubDate('foo_bar_9985');

        $this->assertEquals('', $date);
        $this->assertEquals('foo_bar_9985', $newFileBase);
    }

    public function testExtractPubDateSeparateMonthDayYear()
    {
        $this->assertPubDateForFileBase('1975-03-15', 'foo_bar_March_15_1975');
    }

    public function testExtractPubDateSeparateMonthPrefixDayYear()
    {
        list($date, $newFileBase) = Manx\UrlMetaData::extractPubDate('foo_bar_Marching_15_1975');

        $this->assertEquals('1975', $date);
        $this->assertEquals('foo_bar_Marching_15', $newFileBase);
    }

    public function testExtractPubDateSeparateMonthInvalidDayYear()
    {
        list($date, $newFileBase) = Manx\UrlMetaData::extractPubDate('foo_bar_Marching_32_1975');

        $this->assertEquals('1975', $date);
        $this->assertEquals('foo_bar_Marching_32', $newFileBase);
    }

    public function testExtractPubDateSeparateDayMonthYear()
    {
        $this->assertPubDateForFileBase('1975-03-15', 'foo_bar_15_March_1975');
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
        list($date, $newFileBase) = Manx\UrlMetaData::extractPubDate($fileBase);
        $this->assertEquals($pubDate, $date);
        $this->assertEquals('foo_bar', $newFileBase);
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
        $this->assertTitleForFileBase('TI CBL RealWorldMath Guidebook', 'TI_CBL_RealWorldMath_Guidebook');
    }

    public function testTitleForFileBaseWithMixedCaseTwoWords()
    {
        $this->assertTitleForFileBase('Foo Bar', 'FooBar');
    }

    public function testTitleForFileBaseWithMixedCaseFourWords()
    {
        $this->assertTitleForFileBase('Foo Bar Blobby Phlegm', 'FooBarBlobbyPhlegm');
    }

    public function testTitleForFileBaseWithMixedCaseWordsUnderscoreSeparator()
    {
        $this->assertTitleForFileBase('Foo Bar BlobbyPhlegm', 'Foo_Bar_BlobbyPhlegm');
    }

    private function assertTitleForFileBase($title, $fileBase)
    {
        $this->assertEquals($title, Manx\UrlMetaData::titleForFileBase($fileBase));
    }

    private function createPublicationsForCompare($leftPart, $leftRev, $leftTitle, $rightPart, $rightRev, $rightTitle)
    {
        $columns = array('ph_pub', 'ph_part', 'ph_revision', 'ph_title');
        $left = \Manx\Test\RowFactory::createResultRowsForColumns($columns,
            array(array('1', $leftPart, $leftRev, $leftTitle)));
        $left = $left[0];
        $right = \Manx\Test\RowFactory::createResultRowsForColumns($columns,
            array(array('2', $rightPart, $rightRev, $rightTitle)));
        $right = $right[0];
        return array($left, $right);
    }

    private $_manx;

}
