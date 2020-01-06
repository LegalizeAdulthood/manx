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
-- Update table structure for `site_unknown` to include `dir_id` column
-- Update table structure for `copy` to include `sud_id` column
--
DROP PROCEDURE IF EXISTS `manx_add_site_unknown_dir_id`;
DELIMITER //
CREATE PROCEDURE `manx_add_site_unknown_dir_id`()
BEGIN
    IF EXISTS (SELECT * FROM `information_schema`.`columns` WHERE `table_schema` = SCHEMA() AND `table_name` = 'site_unknown' AND `column_name` = 'dir_id') THEN
        ALTER TABLE `site_unknown` DROP COLUMN `dir_id`;
    END IF;
    ALTER TABLE `site_unknown`
        ADD COLUMN `dir_id` INT(11) NOT NULL DEFAULT -1,
        DROP INDEX `site_id`,
        ADD UNIQUE KEY `site_id`(`site_id`, `path`, `dir_id`);

    IF EXISTS (SELECT * FROM `information_schema`.`columns` WHERE `table_schema` = SCHEMA() AND `table_name` = 'copy' AND `column_name` = 'sud_id') THEN
        ALTER TABLE `copy` DROP COLUMN `sud_id`;
    END IF;
    ALTER TABLE `copy`
        ADD COLUMN `sud_id` INT(11) NOT NULL DEFAULT -1;
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
        SELECT DISTINCT `site_id`, manx_parent_dir(`path`) AS `path`
        FROM `site_unknown`
        WHERE INSTR(`path`, '/') > 0;

    -- Populate directory tree
    SELECT COUNT(*) FROM `site_unknown_dir` WHERE INSTR(`path`, '/') > 0 AND `parent_dir_id` = -1 LIMIT 1 INTO `dir_count`;
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
            AND INSTR(`sud`.`path`, '/') > 0
            AND `sud2`.`path` = manx_parent_dir(`sud`.`path`);

        -- Update count of subdirectories with no parent directory id
        SELECT COUNT(*) FROM `site_unknown_dir` WHERE INSTR(`path`, '/') > 0 AND `parent_dir_id` = -1 LIMIT 1 INTO `dir_count`;
    END WHILE;

    -- Replace directory prefix with directory id in site unknown paths
    UPDATE `site_unknown` `su`, `site_unknown_dir` `sud`
        SET
            `su`.`dir_id` = `sud`.`id`,
            `su`.`path` = SUBSTRING_INDEX(`su`.`path`, '/', -1)
        WHERE
            `su`.`dir_id` = -1
            AND `su`.`path` = CONCAT(`sud`.`path`, '/', SUBSTRING_INDEX(`su`.`path`, '/', -1));
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
            AND NOT `id` IN (SELECT DISTINCT `parent_dir_id` FROM `site_unknown_dir`)
            AND NOT `id` IN (SELECT DISTINCT `sud_id` FROM `copy` WHERE `sud_id` <> -1);

    WHILE (SELECT COUNT(*) FROM `tmp_dir_ids` LIMIT 1) > 0 DO
        DELETE FROM `site_unknown_dir` WHERE `id` IN (SELECT `id` FROM `tmp_dir_ids`);

        DELETE FROM `tmp_dir_ids`;
        INSERT INTO `tmp_dir_ids`
            SELECT `id` FROM `site_unknown_dir`
            WHERE NOT `id` IN (SELECT DISTINCT `dir_id` FROM `site_unknown`)
                AND NOT `id` IN (SELECT DISTINCT `parent_dir_id` FROM `site_unknown_dir`)
                AND NOT `id` IN (SELECT DISTINCT `sud_id` FROM `copy` WHERE `sud_id` <> -1);
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
    WHILE (SELECT COUNT(*) FROM `tmp_dir_ids` LIMIT 1) > 0 DO
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

DROP PROCEDURE IF EXISTS `build_copy_ids`;
DELIMITER //
CREATE PROCEDURE `build_copy_ids`()
BEGIN
    DROP TABLE IF EXISTS `tmp_copy_ids`;
    CREATE TEMPORARY TABLE `tmp_copy_ids`(
        `id` INT(11) NOT NULL,
        `path` VARCHAR(255) NOT NULL
    );

    INSERT INTO `tmp_copy_ids`
        SELECT `c`.`copy_id` AS `id`, SUBSTRING_INDEX(`c`.`url`, '/', -1) AS `path`
            FROM `copy` `c`
            WHERE `c`.`sud_id` = -1
                AND `c`.`site` IN (SELECT DISTINCT `site_id` FROM `site_unknown_dir`);
END//
DELIMITER ;

--
-- `manx_update_copy_sud_ids`
--
-- Update the `sud_id` column in the `copy` table to the correct
-- id from the `site_unknown_dir` table.  This accelerates dropping
-- site_unknown paths for known copies.
--
DROP PROCEDURE IF EXISTS `manx_update_copy_sud_ids`;
DELIMITER //
CREATE PROCEDURE `manx_update_copy_sud_ids`()
BEGIN
    CALL `build_copy_ids`();

    UPDATE `copy`,
        (SELECT `c`.`copy_id`, `sud`.`id` AS `sud_id`
            FROM `site` `s`, `copy` `c`, `site_unknown` `su`, `site_unknown_dir` `sud`, `tmp_copy_ids` `tci`
            WHERE `c`.`copy_id` = `tci`.`id`
                AND `s`.`site_id` = `c`.`site`
                AND `s`.`site_id` = `sud`.`site_id`
                AND `s`.`site_id` = `su`.`site_id`
                AND `su`.`path` = `tci`.`path`
                AND `c`.`url` = CONCAT(`s`.`copy_base`, `sud`.`path`, '/', `su`.`path`))
            AS `tmp`
        SET `copy`.`sud_id` = `tmp`.`sud_id`
        WHERE `copy`.`copy_id` = `tmp`.`copy_id`;
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
    CALL `build_copy_ids`();

    DROP TABLE IF EXISTS `tmp_su_ids`;
    CREATE TEMPORARY TABLE `tmp_su_ids`(`id` INT(11) NOT NULL);
    INSERT INTO `tmp_su_ids`
        SELECT `su`.`id` FROM `site` `s`, `copy` `c`, `site_unknown` `su`, `site_unknown_dir` `sud`, `tmp_copy_ids` `tci`
            WHERE `c`.`copy_id` = `tci`.`id`
                AND `c`.`site` = `s`.`site_id`
                AND `c`.`site` = `su`.`site_id`
                AND `c`.`site` = `sud`.`site_id`
                AND `su`.`dir_id` = `sud`.`id`
                AND `su`.`path` = `tci`.`path`
                AND (
                    `c`.`sud_id` = `sud`.`id`
                    OR `c`.`url` = CONCAT(`s`.`copy_base`, `sud`.`path`, '/', `su`.`path`)
                );

    DELETE FROM `site_unknown` WHERE `id` IN (SELECT `id` FROM `tmp_su_ids`);
END//
DELIMITER ;

--
-- Begin data modification
--
START TRANSACTION;

--
-- Populate remaining directory tree
--
CALL `manx_unknown_directory_migrater`();

--
-- Populate `copy`.`sud_id`
--
CALL `manx_update_copy_sud_ids`();

--
-- Purge site_unknown paths for existing copies
--
CALL `manx_purge_su_copies`();

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
