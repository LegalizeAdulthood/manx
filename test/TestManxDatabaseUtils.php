<?php

require_once 'pages/ManxDatabase.php';

class TestManxDatabaseUtils extends PHPUnit_Framework_TestCase
{
    public function testNormalizePartNumberNotString()
    {
        $this->assertEquals('', ManxDatabase::normalizePartNumber(array()));
    }

    public function testNormalizePartNumberLowerCase()
    {
        $this->assertEquals('UC', ManxDatabase::normalizePartNumber('uc'));
    }

    public function testNormalizePartNumberNonAlphaNumeric()
    {
        $this->assertEquals('UC122', ManxDatabase::normalizePartNumber(' !u,c,1,2,2 ,./<>?;' . "'" . ':"[]{}\\|`~!@#$%^&*()'));
    }

    public function testNormalizePartNumberLetterOhIsZero()
    {
        $this->assertEquals('UC1220', ManxDatabase::normalizePartNumber(' !u,c,1,2,2,o ,./<>?;' . "'" . ':"[]{}\\|`~!@#$%^&*()'));
    }

    public function testCleanSqlWordNotString()
    {
        $this->assertEquals('', ManxDatabase::cleanSqlWord(array()));
    }

    public function testCleanSqlWordNoSpecials()
    {
        $this->assertEquals('cleanWord', ManxDatabase::cleanSqlWord('cleanWord'));
    }

    public function testCleanSqlWordPercent()
    {
        $this->assertEquals('percent\\%Word', ManxDatabase::cleanSqlWord('percent%Word'));
    }

    public function testCleanSqlWordQuote()
    {
        $this->assertEquals("quote\\'Word", ManxDatabase::cleanSqlWord("quote'Word"));
    }

    public function testCleanSqlWordUnderline()
    {
        $this->assertEquals('underline\\_Word', ManxDatabase::cleanSqlWord('underline_Word'));
    }

    public function testCleanSqlWordBackslash()
    {
        $this->assertEquals('backslash\\\\Word', ManxDatabase::cleanSqlWord('backslash\\Word'));
    }

    public function testMatchClauseForSearchWordsSingleKeyword()
    {
        $clause = ManxDatabase::matchClauseForSearchWords(array('terminal'));
        $this->assertEquals(" AND ((`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
            . "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
    }

    public function testMatchClauseForMultipleKeywords()
    {
        $clause = ManxDatabase::matchClauseForSearchWords(array('graphics', 'terminal'));
        $this->assertEquals(" AND ((`ph_title` LIKE '%graphics%' OR `ph_keywords` LIKE '%graphics%' "
            . "OR `ph_match_part` LIKE '%GRAPHICS%' OR `ph_match_alt_part` LIKE '%GRAPHICS%') "
            . "AND (`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
            . "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
    }

    public function testSortPartNumberGRINoMatch()
    {
        $this->assertEquals('XX', ManxDatabase::sortPartNumberGRI('XX'));
    }

    public function testSortPartNumberGRIMatch()
    {
        $this->assertEquals('12034XYZ', ManxDatabase::sortPartNumberGRI('12-34-XYZ'));
    }

    public function testSortPartNumberTeletypeNoMatch()
    {
        $this->assertEquals('XYZ1234', ManxDatabase::sortPartNumberTeletype('XYZ-1234'));
    }

    public function testSortPartNumberTeletypeMatch()
    {
        $this->assertEquals('0123XYZ', ManxDatabase::sortPartNumberTeletype('123-XYZ'));
    }

    public function testSortPartNumberInterdataNoMatch()
    {
        $this->assertEquals('123XYZ', ManxDatabase::sortPartNumberInterdata('123-XYZ'));
    }

    public function testSortPartNumberInterdataMatch()
    {
        $this->assertEquals('123XYZ', ManxDatabase::sortPartNumberInterdata('ABC-123-XYZ'));
    }

    public function testSortPartNumberMotorolaNoMatch()
    {
        $this->assertEquals('123-XYZ', ManxDatabase::sortPartNumberMotorola('123-XYZ'));
    }

    public function testSortPartNumberMotorolaMatch()
    {
        $this->assertEquals('AN01234-XYZ', ManxDatabase::sortPartNumberMotorola('AN1234-XYZ'));
    }

    public function testSortPartNumberIBMNoMatch()
    {
        $this->assertEquals('WXYZ123', ManxDatabase::sortPartNumberIBM('WXYZ-123'));
    }

    public function testSortPartNumberIBMMatch()
    {
        $this->assertEquals('A12345600', ManxDatabase::sortPartNumberIBM('12-3456'));
    }

    public function testSortPartNumberWyseNoMatch()
    {
        $this->assertEquals('WY123', ManxDatabase::sortPartNumberWyse('WY-123'));
    }

    public function testSortPartNumberWyseMatch()
    {
        $this->assertEquals('12034567', ManxDatabase::sortPartNumberWyse('12-345-67'));
    }

    public function testSortPartNumberVisualNoMatch()
    {
        $this->assertEquals('1121', ManxDatabase::sortPartNumberVisual('1121'));
    }

    public function testSortPartNumberVisualMatch()
    {
        $this->assertEquals('123', ManxDatabase::sortPartNumberVisual('AB-123-XY'));
    }

    public function testSortPartNumberTeleVideoNoMatch()
    {
        $this->assertEquals('12345678', ManxDatabase::sortPartNumberTeleVideo('1234-5678'));
    }

    public function testSortPartNumberTeleVideoMatch()
    {
        $this->assertEquals('0300013001', ManxDatabase::sortPartNumberTeleVideo('B300013-001'));
    }

    public function testSortPartNumberTIMatch()
    {
        $this->assertEquals('01234561234', ManxDatabase::sortPartNumberTI('123456-1234'));
    }

    public function testSortPartNumberTINoMatch()
    {
        $this->assertEquals('XYZ123', ManxDatabase::sortPartNumberTI('XYZ-123'));
    }

    public function testSortPartNumberDECMatch()
    {
        $this->assertEquals('FOO000', ManxDatabase::sortPartNumberDEC('FOO-PRE9969'));
    }

    public function testSortPartNumberDECMatchRT11()
    {
        $this->assertEquals('ADC740009', ManxDatabase::sortPartNumberDEC('ADC7400B9'));
    }

    public function testSortPartNumberDECNoMatch()
    {
        $this->assertEquals('XYZ123', ManxDatabase::sortPartNumberDEC('XYZ-123'));
    }
}
