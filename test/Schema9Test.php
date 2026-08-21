<?php

class Schema9Test extends PHPUnit\Framework\TestCase
{
    public function testPdfMetadataSizePropertyIsCreated()
    {
        $sql = self::schemaSql();

        $this->assertStringContainsString(
            "INSERT INTO `properties` (`name`, `value`)",
            $sql);
        $this->assertStringContainsString(
            "VALUES ('pdf_metadata_max_bytes', '4194304')",
            $sql);
        $this->assertStringContainsString(
            "ON DUPLICATE KEY UPDATE `value` = `value`",
            $sql);
    }

    public function testFinalStatementSetsVersion()
    {
        $sql = self::schemaSql();

        $this->assertMatchesRegularExpression(
            "/UPDATE `properties`\s+SET `value` = '2\\.2\\.0'\s+WHERE `name` = 'version';\s*$/",
            $sql);
    }

    private static function schemaSql()
    {
        return file_get_contents(__DIR__ . '/../schema/9-schema.sql');
    }
}
