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

    public function testSiteUnknownPdfMetadataColumnsAreAdded()
    {
        $sql = self::schemaSql();

        $this->assertStringContainsString(
            "ALTER TABLE `site_unknown`\r\n"
            . "  ADD COLUMN `pdf_title` VARCHAR(255) NOT NULL DEFAULT '',\r\n"
            . "  ADD COLUMN `pdf_keywords` VARCHAR(100) NOT NULL DEFAULT '',\r\n"
            . "  ADD COLUMN `pdf_abstract` VARCHAR(2048) NOT NULL DEFAULT '',\r\n"
            . "  ADD COLUMN `pdf_notes` VARCHAR(200) NOT NULL DEFAULT '',\r\n"
            . "  ADD COLUMN `pdf_credits` VARCHAR(200) NOT NULL DEFAULT '',\r\n"
            . "  ADD COLUMN `pdf_metadata_status` VARCHAR(16) NOT NULL DEFAULT '',\r\n"
            . "  ADD COLUMN `pdf_metadata_error` VARCHAR(255) NOT NULL DEFAULT '',\r\n"
            . "  ADD COLUMN `pdf_metadata_checked` DATETIME NULL DEFAULT NULL;",
            $sql);
    }

    public function testSiteUnknownPdfMetadataColumnsPrecedeVersionUpdate()
    {
        $sql = self::schemaSql();

        $columns = strpos($sql, 'ALTER TABLE `site_unknown`');
        $version = strrpos($sql, "SET `value` = '2.2.0'");
        $this->assertNotFalse($columns);
        $this->assertLessThan($version, $columns);
    }

    public function testFinalStatementSetsVersion()
    {
        $sql = self::schemaSql();

        $this->assertMatchesRegularExpression(
            "/UPDATE `properties`\s+SET `value` = '2\\.2\\.0'\s+WHERE `name` = 'version';\s*$/",
            $sql);
    }

    public function testCopyUrlMigrationUsesTemporaryProcedures()
    {
        $sql = self::schemaSql();

        $this->assertStringContainsString(
            'CREATE PROCEDURE `manx_normalize_copy_urls`()',
            $sql);
        $this->assertStringContainsString(
            'DROP PROCEDURE IF EXISTS `manx_normalize_copy_urls`;',
            $sql);
        $this->assertStringNotContainsString('ALTER TABLE `copy`', $sql);
        $this->assertStringNotContainsString('CREATE TABLE `copy`', $sql);
        $this->assertStringNotContainsString('CREATE FUNCTION', $sql);
    }

    public function testCopyUrlMigrationEncodesPathCharacters()
    {
        $sql = self::schemaSql();

        $this->assertStringContainsString(
            "ELSEIF `ch` REGEXP '^[-A-Za-z0-9._~/]$' THEN",
            $sql);
        $this->assertStringContainsString(
            "LPAD(HEX(ASCII(`ch`)), 2, '0')",
            $sql);
    }

    public function testCopyUrlMigrationPreservesEncodedBytes()
    {
        $sql = self::schemaSql();

        $this->assertStringContainsString(
            "SUBSTRING(`source_path`, `i` + 1, 2)",
            $sql);
        $this->assertStringContainsString(
            "REGEXP '^[0-9A-Fa-f][0-9A-Fa-f]$'",
            $sql);
        $this->assertStringContainsString(
            "UPPER(SUBSTRING(`source_path`, `i` + 1, 2))",
            $sql);
    }

    public function testCopyUrlMigrationChecksCollisionsBeforeUpdate()
    {
        $sql = self::schemaSql();

        $signal = strpos($sql, "SIGNAL SQLSTATE '45000'");
        $update = strpos($sql, "UPDATE `copy` `c`, `tmp_copy_url_normalized` `n`");
        $this->assertNotFalse($signal);
        $this->assertNotFalse($update);
        $this->assertLessThan($update, $signal);
        $this->assertStringContainsString(
            'HAVING COUNT(DISTINCT `url`) > 1',
            $sql);
    }

    public function testCopyUrlMigrationDropsProceduresBeforeVersionUpdate()
    {
        $sql = self::schemaSql();

        $drop = strpos($sql,
            'DROP PROCEDURE IF EXISTS `manx_normalize_copy_urls`;',
            strpos($sql, 'CALL `manx_normalize_copy_urls`();'));
        $version = strrpos($sql, "SET `value` = '2.2.0'");
        $this->assertNotFalse($drop);
        $this->assertLessThan($version, $drop);
    }

    public function testSiteUrlMigrationUsesTemporaryProcedure()
    {
        $sql = self::schemaSql();

        $this->assertStringContainsString(
            'CREATE PROCEDURE `manx_upgrade_site_urls_to_https`()',
            $sql);
        $this->assertStringContainsString(
            "UPDATE `site`\r\n"
            . "        SET `url` = CONCAT('https://', SUBSTRING(`url`, 8))\r\n"
            . "        WHERE `url` LIKE 'http://%';",
            $sql);
        $this->assertStringContainsString(
            "UPDATE `site`\r\n"
            . "        SET `copy_base` = CONCAT('https://', "
            . "SUBSTRING(`copy_base`, 8))\r\n"
            . "        WHERE `copy_base` LIKE 'http://%';",
            $sql);
        $this->assertStringContainsString(
            'DROP PROCEDURE IF EXISTS `manx_upgrade_site_urls_to_https`;',
            $sql);
    }

    public function testSiteUrlMigrationDropsProcedureBeforeVersionUpdate()
    {
        $sql = self::schemaSql();

        $drop = strrpos($sql,
            'DROP PROCEDURE IF EXISTS `manx_upgrade_site_urls_to_https`;');
        $version = strrpos($sql, "SET `value` = '2.2.0'");
        $this->assertNotFalse($drop);
        $this->assertLessThan($version, $drop);
    }

    public function testSiteUnknownDirPartRegexDefaultIsAltered()
    {
        $sql = self::schemaSql();

        $this->assertStringContainsString(
            "ALTER TABLE `site_unknown_dir`\r\n"
            . "  ALTER COLUMN `part_regex`\r\n"
            . "  SET DEFAULT '^([^_]*[0-9][0-9][^_]*)_';",
            $sql);
        $this->assertStringNotContainsString(
            'ADD COLUMN `part_regex`',
            $sql);
    }

    public function testSiteUnknownDirPartRegexBackfillUsesTemporaryProcedure()
    {
        $sql = self::schemaSql();

        $this->assertStringContainsString(
            'CREATE PROCEDURE `manx_backfill_site_unknown_dir_part_regex`()',
            $sql);
        $this->assertStringContainsString(
            "UPDATE `site_unknown_dir`\r\n"
            . "        SET `part_regex` = '^([^_]*[0-9][0-9][^_]*)_'\r\n"
            . "        WHERE `part_regex` = '';",
            $sql);
        $this->assertStringContainsString(
            'DROP PROCEDURE IF EXISTS '
            . '`manx_backfill_site_unknown_dir_part_regex`;',
            $sql);
    }

    public function testSiteUnknownDirPartRegexBackfillDropsBeforeVersionUpdate()
    {
        $sql = self::schemaSql();

        $drop = strrpos($sql,
            'DROP PROCEDURE IF EXISTS '
            . '`manx_backfill_site_unknown_dir_part_regex`;');
        $version = strrpos($sql, "SET `value` = '2.2.0'");
        $this->assertNotFalse($drop);
        $this->assertLessThan($version, $drop);
    }

    private static function schemaSql()
    {
        return file_get_contents(__DIR__ . '/../schema/9-schema.sql');
    }
}
