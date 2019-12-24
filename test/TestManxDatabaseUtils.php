<?php

require_once 'pages/ManxDatabase.php';

class TestManxDatabaseUtils extends PHPUnit\Framework\TestCase
{
    public function testNormalizePartNumberNotString()
    {
        $this->assertEquals('', Manx\ManxDatabase::normalizePartNumber(array()));
    }

    public function testNormalizePartNumberLowerCase()
    {
        $this->assertEquals('UC', Manx\ManxDatabase::normalizePartNumber('uc'));
    }

    public function testNormalizePartNumberNonAlphaNumeric()
    {
        $this->assertEquals('UC122', Manx\ManxDatabase::normalizePartNumber(' !u,c,1,2,2 ,./<>?;' . "'" . ':"[]{}\\|`~!@#$%^&*()'));
    }

    public function testNormalizePartNumberLetterOhIsZero()
    {
        $this->assertEquals('UC1220', Manx\ManxDatabase::normalizePartNumber(' !u,c,1,2,2,o ,./<>?;' . "'" . ':"[]{}\\|`~!@#$%^&*()'));
    }

    public function testCleanSqlWordNotString()
    {
        $this->assertEquals('', Manx\ManxDatabase::cleanSqlWord(array()));
    }

    public function testCleanSqlWordNoSpecials()
    {
        $this->assertEquals('cleanWord', Manx\ManxDatabase::cleanSqlWord('cleanWord'));
    }

    public function testCleanSqlWordPercent()
    {
        $this->assertEquals('percent\\%Word', Manx\ManxDatabase::cleanSqlWord('percent%Word'));
    }

    public function testCleanSqlWordQuote()
    {
        $this->assertEquals("quote\\'Word", Manx\ManxDatabase::cleanSqlWord("quote'Word"));
    }

    public function testCleanSqlWordUnderline()
    {
        $this->assertEquals('underline\\_Word', Manx\ManxDatabase::cleanSqlWord('underline_Word'));
    }

    public function testCleanSqlWordBackslash()
    {
        $this->assertEquals('backslash\\\\Word', Manx\ManxDatabase::cleanSqlWord('backslash\\Word'));
    }

    public function testMatchClauseForSearchWordsSingleKeyword()
    {
        $clause = Manx\ManxDatabase::matchClauseForSearchWords(array('terminal'));
        $this->assertEquals(" AND ((`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
            . "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
    }

    public function testMatchClauseForMultipleKeywords()
    {
        $clause = Manx\ManxDatabase::matchClauseForSearchWords(array('graphics', 'terminal'));
        $this->assertEquals(" AND ((`ph_title` LIKE '%graphics%' OR `ph_keywords` LIKE '%graphics%' "
            . "OR `ph_match_part` LIKE '%GRAPHICS%' OR `ph_match_alt_part` LIKE '%GRAPHICS%') "
            . "AND (`ph_title` LIKE '%terminal%' OR `ph_keywords` LIKE '%terminal%' "
            . "OR `ph_match_part` LIKE '%TERMINAL%' OR `ph_match_alt_part` LIKE '%TERMINAL%'))", $clause);
    }

    public function testSortPartNumberGRINoMatch()
    {
        $this->assertEquals('XX', Manx\ManxDatabase::sortPartNumberGRI('XX'));
    }

    public function testSortPartNumberGRIMatch()
    {
        $this->assertEquals('12034XYZ', Manx\ManxDatabase::sortPartNumberGRI('12-34-XYZ'));
    }

    public function testSortPartNumberTeletypeNoMatch()
    {
        $this->assertEquals('XYZ1234', Manx\ManxDatabase::sortPartNumberTeletype('XYZ-1234'));
    }

    public function testSortPartNumberTeletypeMatch()
    {
        $this->assertEquals('0123XYZ', Manx\ManxDatabase::sortPartNumberTeletype('123-XYZ'));
    }

    public function testSortPartNumberInterdataNoMatch()
    {
        $this->assertEquals('123XYZ', Manx\ManxDatabase::sortPartNumberInterdata('123-XYZ'));
    }

    public function testSortPartNumberInterdataMatch()
    {
        $this->assertEquals('123XYZ', Manx\ManxDatabase::sortPartNumberInterdata('ABC-123-XYZ'));
    }

    public function testSortPartNumberMotorolaNoMatch()
    {
        $this->assertEquals('123-XYZ', Manx\ManxDatabase::sortPartNumberMotorola('123-XYZ'));
    }

    public function testSortPartNumberMotorolaMatch()
    {
        $this->assertEquals('AN01234-XYZ', Manx\ManxDatabase::sortPartNumberMotorola('AN1234-XYZ'));
    }

    public function testSortPartNumberIBMNoMatch()
    {
        $this->assertEquals('WXYZ123', Manx\ManxDatabase::sortPartNumberIBM('WXYZ-123'));
    }

    public function testSortPartNumberIBMMatch()
    {
        $this->assertEquals('A12345600', Manx\ManxDatabase::sortPartNumberIBM('12-3456'));
    }

    public function testSortPartNumberWyseNoMatch()
    {
        $this->assertEquals('WY123', Manx\ManxDatabase::sortPartNumberWyse('WY-123'));
    }

    public function testSortPartNumberWyseMatch()
    {
        $this->assertEquals('12034567', Manx\ManxDatabase::sortPartNumberWyse('12-345-67'));
    }

    public function testSortPartNumberVisualNoMatch()
    {
        $this->assertEquals('1121', Manx\ManxDatabase::sortPartNumberVisual('1121'));
    }

    public function testSortPartNumberVisualMatch()
    {
        $this->assertEquals('123', Manx\ManxDatabase::sortPartNumberVisual('AB-123-XY'));
    }

    public function testSortPartNumberTeleVideoNoMatch()
    {
        $this->assertEquals('12345678', Manx\ManxDatabase::sortPartNumberTeleVideo('1234-5678'));
    }

    public function testSortPartNumberTeleVideoMatch()
    {
        $this->assertEquals('0300013001', Manx\ManxDatabase::sortPartNumberTeleVideo('B300013-001'));
    }

    public function testSortPartNumberTIMatch()
    {
        $this->assertEquals('01234561234', Manx\ManxDatabase::sortPartNumberTI('123456-1234'));
    }

    public function testSortPartNumberTINoMatch()
    {
        $this->assertEquals('XYZ123', Manx\ManxDatabase::sortPartNumberTI('XYZ-123'));
    }

    public function testSortPartNumberDECMatch()
    {
        $this->assertEquals('FOO000', Manx\ManxDatabase::sortPartNumberDEC('FOO-PRE9969'));
    }

    public function testSortPartNumberDECMatchRT11()
    {
        $this->assertEquals('ADC740009', Manx\ManxDatabase::sortPartNumberDEC('ADC7400B9'));
    }

    public function testSortPartNumberDECNoMatch()
    {
        $this->assertEquals('XYZ123', Manx\ManxDatabase::sortPartNumberDEC('XYZ-123'));
    }
}
