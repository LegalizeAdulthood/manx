--
-- Begin schema modification
--

--
-- Table structure for table `site_unknown_dir`
--
DROP TABLE IF EXISTS `site_unknown_dir`;
CREATE TABLE site_unknown_dir (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `site_id` INT(11) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `parent_dir_id` INT(11) NOT NULL DEFAULT -1,
  `part_regex` VARCHAR(255) NOT NULL DEFAULT '',
  `ignored` INT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE (`site_id`, `path`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- `manx_add_site_unknown_dir_id`
--
-- Update table structure for `site_unknown` to separate directory id and
-- filename columns.
--
DROP PROCEDURE IF EXISTS `manx_add_site_unknown_dir_id`;
DELIMITER //
CREATE PROCEDURE `manx_add_site_unknown_dir_id`()
BEGIN
    IF EXISTS (SELECT * FROM `information_schema`.`columns` WHERE `table_schema` = SCHEMA() AND `table_name` = 'site_unknown' AND `column_name` = 'dir_id') THEN
        ALTER TABLE `site_unknown` DROP COLUMN `dir_id`;
    END IF;
    ALTER TABLE `site_unknown`
        CHANGE COLUMN `path` `filename` VARCHAR(255) NOT NULL,
        ADD COLUMN `dir_id` INT(11) NOT NULL DEFAULT -1,
        DROP INDEX `site_id`,
        ADD UNIQUE KEY `site_id`(`site_id`, `filename`, `dir_id`);

    ALTER TABLE `copy`
        ADD COLUMN `filename` VARCHAR(255) NOT NULL DEFAULT '',
        ADD KEY `site_filename`(`site`, `filename`);
END//
DELIMITER ;

CALL manx_add_site_unknown_dir_id();

--
-- End schema modification
--

--
-- `manx_parent_dir`
-- 
-- Returns the parent directory string of a path.
--
DROP FUNCTION IF EXISTS `manx_parent_dir`;
CREATE FUNCTION `manx_parent_dir`(`path` VARCHAR(255))
    RETURNS VARCHAR(255) DETERMINISTIC
    RETURN SUBSTR(`path`, 1, LENGTH(`path`) - 1 - LENGTH(SUBSTRING_INDEX(`path`, '/', -1)));

--
-- `manx_unknown_directory_migrater`
--
-- Creates complete directory hierarchies from existing directories in the
-- site_unknown_dir table.  As long as directory paths containing '/' exist
-- with a parent directory id of -1, insert parent directories and update
-- parent directory ids.
--
DROP PROCEDURE IF EXISTS `manx_unknown_directory_migrater`;
DELIMITER //
CREATE PROCEDURE `manx_unknown_directory_migrater`()
BEGIN
    DECLARE `dir_count` INT(11);

    -- Initial site unknown directories from site unknown paths
    INSERT INTO `site_unknown_dir`(`site_id`, `path` )
        SELECT DISTINCT `site_id`, manx_parent_dir(`filename`) AS `path`
        FROM `site_unknown`
        WHERE INSTR(`filename`, '/') > 0;

    -- Populate directory tree
    SELECT COUNT(*) FROM `site_unknown_dir` WHERE INSTR(`path`, '/') > 0 AND `parent_dir_id` = -1 INTO `dir_count`;
    WHILE `dir_count` > 0 DO
        -- Insert parent directories from existing directories
        INSERT INTO `site_unknown_dir`(`site_id`, `path`)
            SELECT DISTINCT
                `sud2`.`site_id`,
                manx_parent_dir(`sud2`.`path`) AS `path`
            FROM
                `site_unknown_dir` `sud2`
            WHERE
                `sud2`.`parent_dir_id` = -1
                AND INSTR(`sud2`.`path`, '/') > 0
            ON DUPLICATE KEY UPDATE
                `site_unknown_dir`.`site_id` = `site_unknown_dir`.`site_id`;

        -- Update parent directory ids
        UPDATE
            `site_unknown_dir` `sud`,
            `site_unknown_dir` `sud2`
        SET
            `sud`.`parent_dir_id` = `sud2`.`id`
        WHERE
            `sud`.`parent_dir_id` = -1
            AND `sud`.`site_id` = `sud2`.`site_id`
            AND INSTR(`sud`.`path`, '/') > 0
            AND `sud2`.`path` = manx_parent_dir(`sud`.`path`);

        -- Update count of subdirectories with no parent directory id
        SELECT COUNT(*) FROM `site_unknown_dir` WHERE INSTR(`path`, '/') > 0 AND `parent_dir_id` = -1 INTO `dir_count`;
    END WHILE;

    -- Replace directory prefix with directory id in site unknown paths
    UPDATE `site_unknown` `su`, `site_unknown_dir` `sud`
        SET
            `su`.`dir_id` = `sud`.`id`,
            `su`.`filename` = SUBSTRING_INDEX(`su`.`filename`, '/', -1)
        WHERE
            `su`.`dir_id` = -1
            AND `su`.`site_id` = `sud`.`site_id`
            AND `sud`.`path` = manx_parent_dir(`su`.`filename`);
END//
DELIMITER ;

--
-- `manx_purge_unused_unknown_directories`
--
DROP PROCEDURE IF EXISTS `manx_purge_unused_unknown_directories`;
DELIMITER //
CREATE PROCEDURE `manx_purge_unused_unknown_directories`()
BEGIN
    DROP TABLE IF EXISTS `tmp_dir_ids`;
    CREATE TEMPORARY TABLE `tmp_dir_ids`(`id` INT(11) NOT NULL);

    INSERT INTO `tmp_dir_ids`
        SELECT `id` FROM `site_unknown_dir`
        WHERE NOT `id` IN (SELECT DISTINCT `dir_id` FROM `site_unknown`)
        AND NOT `id` IN (SELECT DISTINCT `parent_dir_id` FROM `site_unknown_dir`);

    WHILE (SELECT COUNT(*) FROM `tmp_dir_ids`) > 0 DO
        DELETE FROM `site_unknown_dir` WHERE `id` IN (SELECT `id` FROM `tmp_dir_ids`);

        DELETE FROM `tmp_dir_ids`;
        INSERT INTO `tmp_dir_ids`
            SELECT `id` FROM `site_unknown_dir`
            WHERE NOT `id` IN (SELECT DISTINCT `dir_id` FROM `site_unknown`)
            AND NOT `id` IN (SELECT DISTINCT `parent_dir_id` FROM `site_unknown_dir`);
    END WHILE;
END//
DELIMITER ;

--
-- `manx_update_unknown_dir_ignored`
--
-- Propagate ignore status up the directory tree to avoid displaying
-- directories with no unignored documents in the user interface.
--
DROP PROCEDURE IF EXISTS `manx_update_unknown_dir_ignored`;
DELIMITER //
CREATE PROCEDURE `manx_update_unknown_dir_ignored`() 
BEGIN
    -- Set all directories to not ignored
    UPDATE `site_unknown_dir` SET `ignored` = 0;

    -- Create table of all dir ids containing at least one unignored path
    DROP TABLE IF EXISTS `tmp_dir_ids_not_ignored`;
    CREATE TEMPORARY TABLE `tmp_dir_ids_not_ignored`(`id` INT(11) NOT NULL);
    INSERT INTO `tmp_dir_ids_not_ignored`
        SELECT DISTINCT `dir_id` FROM `site_unknown` WHERE `ignored` = 0;

    -- Create table of all leaf dir ids
    DROP TABLE IF EXISTS `tmp_dir_ids`;
    CREATE TEMPORARY TABLE `tmp_dir_ids`(`id` INT(11) NOT NULL);
    INSERT INTO `tmp_dir_ids`
        SELECT `id` FROM `site_unknown_dir`
        WHERE NOT (`id` IN (SELECT DISTINCT `parent_dir_id` FROM `site_unknown_dir`));

    DROP TABLE IF EXISTS `tmp_dir_ids2`;
    CREATE TEMPORARY TABLE `tmp_dir_ids2`(`id` INT(11) NOT NULL);

    -- Propagate ignored status up the directory hierarchy
    WHILE (SELECT COUNT(*) FROM `tmp_dir_ids`) > 0 DO
        SELECT COUNT(*) AS `Processing` FROM `tmp_dir_ids`;

        -- Drop dir ids with unignored paths
        DELETE FROM `tmp_dir_ids` WHERE `id` IN (SELECT `id` FROM `tmp_dir_ids_not_ignored`);

        -- Get all dir ids with at least one child dir that is not ignored
        DELETE FROM `tmp_dir_ids2`;
        INSERT INTO `tmp_dir_ids2` 
            SELECT `tdi`.`id` FROM `tmp_dir_ids` `tdi`, `site_unknown_dir` `sud`
            WHERE `sud`.`parent_dir_id` = `tdi`.`id`
            AND `sud`.`ignored` = 0;

        -- Drop parent dir ids with unignored child dirs
        DELETE FROM `tmp_dir_ids` WHERE `id` IN (SELECT `id` FROM `tmp_dir_ids2`);

        -- Mark all remaining dir ids as ignored
        UPDATE `site_unknown_dir`
            SET `ignored` = 1
            WHERE `id` IN (SELECT `id` FROM `tmp_dir_ids`);

        -- Get all parent dir ids
        DELETE FROM `tmp_dir_ids2`;
        INSERT INTO `tmp_dir_ids2`
            SELECT DISTINCT `sud`.`parent_dir_id` AS `id` FROM `site_unknown_dir` `sud`, `tmp_dir_ids` `tdi`
            WHERE `tdi`.`id` = `sud`.`id`;

        -- Replace tmp_dir_ids with tmp_dir_ids2
        DELETE FROM `tmp_dir_ids`;
        INSERT INTO `tmp_dir_ids` SELECT `id` FROM `tmp_dir_ids2`;
    END WHILE;
END//
DELIMITER ;

--
-- `manx_update_unknown_single_dir_ignored`
--
-- Propagate ignore status up the directory tree for a single directory.
--
DROP PROCEDURE IF EXISTS `manx_update_unknown_single_dir_ignored`;
DELIMITER //
CREATE PROCEDURE `manx_update_unknown_single_dir_ignored`(`su_id` INT(11)) 
BEGIN
    DECLARE `sud_id` INT(11);
    START TRANSACTION;
    SELECT `dir_id` FROM `site_unknown` WHERE `id` = `su_id` INTO `sud_id`;
    WHILE (`sud_id` <> -1
        AND (SELECT COUNT(*)
            FROM `site_unknown`
            WHERE `dir_id` = `sud_id`
            AND `ignored` = 0
            AND `id` <> `su_id`) = 0
        AND (SELECT COUNT(*)
            FROM `site_unknown_dir`
            WHERE `parent_dir_id` = `sud_id`
            AND `ignored` = 0) = 0)
    DO
        UPDATE `site_unknown_dir` SET `ignored` = 1 WHERE `id` = `sud_id`;
        SELECT `parent_dir_id` FROM `site_unknown_dir` WHERE `id` = `sud_id` INTO `sud_id`;
    END WHILE;
    COMMIT;
END//
DELIMITER ;

-- `manx_build_unknown_urls`
--
-- Populate `tmp_su_urls` with the ids of unknown paths and their URLs.
-- This accelerates queries against unknown paths against known copies.
--
DROP PROCEDURE IF EXISTS `manx_build_unknown_urls`;
DELIMITER //
CREATE PROCEDURE `manx_build_unknown_urls`()
BEGIN
    DROP TABLE IF EXISTS `tmp_su_urls`;
    CREATE TEMPORARY TABLE `tmp_su_urls`(`id` INT(11) NOT NULL, `url` VARCHAR(255));
    INSERT INTO `tmp_su_urls`
        SELECT `su`.`id`, CONCAT(`s`.`copy_base`, `sud`.`path`, '/', `su`.`filename`) AS `url`
        FROM `site` `s`, `site_unknown` `su`, `site_unknown_dir` `sud`
        WHERE `s`.`site_id` = `su`.`site_id`
        AND `s`.`site_id` = `sud`.`site_id`
        AND `su`.`dir_id` = `sud`.`id`;
END//
DELIMITER ;

--
-- `manx_decode_url_component`
--
-- Decode URL special-character encodings in a single path component.
--
DROP PROCEDURE IF EXISTS `manx_decode_url_component`;
DELIMITER //
CREATE PROCEDURE `manx_decode_url_component`(
    IN `encoded_component` VARCHAR(255),
    OUT `decoded_component` VARCHAR(255))
BEGIN
    DECLARE `i` INT DEFAULT 1;
    DECLARE `component_len` INT DEFAULT 0;
    DECLARE `hex_byte` CHAR(2) DEFAULT '';

    SET `decoded_component` = '';
    SET `component_len` = CHAR_LENGTH(`encoded_component`);
    WHILE `i` <= `component_len` DO
        IF SUBSTRING(`encoded_component`, `i`, 1) = '%'
            AND `i` + 2 <= `component_len`
            AND SUBSTRING(`encoded_component`, `i` + 1, 2)
                REGEXP '^[0-9A-Fa-f][0-9A-Fa-f]$' THEN
            SET `hex_byte` = SUBSTRING(`encoded_component`, `i` + 1, 2);
            SET `decoded_component` = CONCAT(`decoded_component`,
                CHAR(CONV(`hex_byte`, 16, 10)));
            SET `i` = `i` + 3;
        ELSE
            SET `decoded_component` = CONCAT(`decoded_component`,
                SUBSTRING(`encoded_component`, `i`, 1));
            SET `i` = `i` + 1;
        END IF;
    END WHILE;
END//
DELIMITER ;

--
-- `manx_backfill_copy_filename`
--
-- Populate the copy filename cache from decoded URL basenames.
--
DROP PROCEDURE IF EXISTS `manx_backfill_copy_filename`;
DELIMITER //
CREATE PROCEDURE `manx_backfill_copy_filename`()
BEGIN
    DECLARE `done` INT DEFAULT 0;
    DECLARE `current_copy_id` INT DEFAULT 0;
    DECLARE `encoded_filename` VARCHAR(255) DEFAULT '';
    DECLARE `decoded_filename` VARCHAR(255) DEFAULT '';
    DECLARE `copy_filenames` CURSOR FOR
        SELECT `copy_id`, IFNULL(SUBSTRING_INDEX(`url`, '/', -1), '')
        FROM `copy`
        WHERE `filename` = '';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET `done` = 1;

    OPEN `copy_filenames`;

    copy_loop: LOOP
        FETCH `copy_filenames` INTO `current_copy_id`, `encoded_filename`;
        IF `done` = 1 THEN
            LEAVE copy_loop;
        END IF;
        CALL `manx_decode_url_component`(
            `encoded_filename`, `decoded_filename`);
        UPDATE `copy`
            SET `filename` = `decoded_filename`
            WHERE `copy_id` = `current_copy_id`;
    END LOOP;

    CLOSE `copy_filenames`;
END//
DELIMITER ;

--
-- `manx_purge_su_copies`
--
-- Purge rows from site_unknown that correspond to existing known document copies.
--
DROP PROCEDURE IF EXISTS `manx_purge_su_copies`;
DELIMITER //
CREATE PROCEDURE `manx_purge_su_copies`()
BEGIN
    CALL `manx_build_unknown_urls`();

    DROP TABLE IF EXISTS `tmp_su_ids`;
    CREATE TEMPORARY TABLE `tmp_su_ids`(`id` INT(11) NOT NULL);
    INSERT INTO `tmp_su_ids`
        SELECT `su`.`id`
        FROM `copy` `c`, `site_unknown` `su`, `tmp_su_urls` `tsu`
        WHERE `su`.`id` = `tsu`.`id`
        AND `c`.`site` = `su`.`site_id`
        AND `c`.`filename` = `su`.`filename`
        AND `c`.`url` = `tsu`.`url`;

    DELETE FROM `site_unknown` WHERE `id` IN (SELECT `id` FROM `tmp_su_ids`);
END//
DELIMITER ;

--
-- Convert application tables from MyISAM to InnoDB.
--
-- Production migration path:
-- 1. Take and verify a full database backup.
-- 2. Schedule a maintenance window; ALTER TABLE copies table data and can
--    hold metadata locks while each table is converted.
-- 3. Apply this schema file.
-- 4. Verify that no application tables remain on MyISAM:
--
--        SELECT `table_name`, `engine`
--        FROM `information_schema`.`tables`
--        WHERE `table_schema` = SCHEMA()
--          AND `table_type` = 'BASE TABLE'
--          AND `engine` <> 'InnoDB';
--
-- 5. Smoke test transaction-wrapped updates such as copy ingestion,
--    unknown-path cleanup, and moved-file cleanup.
-- 6. Roll back by restoring the verified backup if conversion fails.
--
-- The schema has no FULLTEXT or SPATIAL indexes.  Existing primary,
-- unique, and ordinary secondary indexes are supported by InnoDB.
--
DROP PROCEDURE IF EXISTS `manx_convert_myisam_tables_to_innodb`;
DELIMITER //
CREATE PROCEDURE `manx_convert_myisam_tables_to_innodb`()
BEGIN
    DECLARE `finished` INT DEFAULT 0;
    DECLARE `current_table_name` VARCHAR(64);
    DECLARE `tables_to_convert` CURSOR FOR
        SELECT `table_name`
        FROM `information_schema`.`tables`
        WHERE `table_schema` = SCHEMA()
        AND `table_type` = 'BASE TABLE'
        AND `engine` = 'MyISAM';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET `finished` = 1;

    OPEN `tables_to_convert`;

    convert_loop: LOOP
        FETCH `tables_to_convert` INTO `current_table_name`;
        IF `finished` THEN
            LEAVE convert_loop;
        END IF;

        SET @alter_table = CONCAT('ALTER TABLE `',
            REPLACE(`current_table_name`, '`', '``'), '` ENGINE=InnoDB');
        PREPARE alter_table_statement FROM @alter_table;
        EXECUTE alter_table_statement;
        DEALLOCATE PREPARE alter_table_statement;
    END LOOP;

    CLOSE `tables_to_convert`;
END//
DELIMITER ;

CALL `manx_convert_myisam_tables_to_innodb`();

SET @alter_table = NULL;

--
-- Begin data modification
--
START TRANSACTION;

--
-- Populate remaining directory tree
--
CALL `manx_unknown_directory_migrater`();

--
-- Populate copy filename cache
--
CALL `manx_backfill_copy_filename`();

--
-- Purge site_unknown paths for existing copies
--
CALL `manx_purge_su_copies`();
CALL `manx_purge_unused_unknown_directories`();
CALL `manx_update_unknown_dir_ignored`();

--
-- ChiClassicComp has become Vintage Technology Digital Archive,
-- and documents have moved around.  With so few copies registered
-- in the database, just nuke all the stuff.
--
DELETE FROM `properties`       WHERE `name`    = 'chiclassiccomp_whats_new_timestamp';
DELETE FROM `copy`             WHERE `site`    = 58;
DELETE FROM `site_company_dir` WHERE `site_id` = 58;
DELETE FROM `site_unknown`     WHERE `site_id` = 58;
DELETE FROM `site_unknown_dir` WHERE `site_id` = 58;
UPDATE `site`
    SET
        `name` = 'VTDA',
        `url` = 'http://vtda.org/',
        `description` = 'The Vintage Technology Digital Archive',
        `copy_base` = 'http://vtda.org/docs/'
    WHERE `site_id` = 58;

--
-- textfiles.com is not an official mirror
--
DELETE FROM `mirror` WHERE `copy_stem` = 'http://www.textfiles.com/bitsavers/';
DELETE FROM `mirror` WHERE `copy_stem` = 'http://www.textfiles.com/bitsavers/www.computer.museum.uq.edu.au/';

--
-- Manx version 2.1.0
--
UPDATE `properties`
    SET `value` = '2.1.0'
    WHERE `name` = 'version';

--
-- End data modification
--
COMMIT;

--
-- Migration cleanup
--
DROP PROCEDURE IF EXISTS `manx_unknown_directory_migrater`;
DROP PROCEDURE IF EXISTS `manx_add_site_unknown_dir_id`;
DROP PROCEDURE IF EXISTS `manx_backfill_copy_filename`;
DROP PROCEDURE IF EXISTS `manx_decode_url_component`;
DROP PROCEDURE IF EXISTS `manx_convert_myisam_tables_to_innodb`;
