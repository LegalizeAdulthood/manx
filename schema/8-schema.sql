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
-- Table structure for table `site_unknown_dir`
--
DROP TABLE IF EXISTS `site_unknown_dir`;
CREATE TABLE site_unknown_dir (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `site_id` INT(11) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `parent_dir_id` INT(11) NOT NULL DEFAULT -1,
  `part_regex` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE (`site_id`, `path`, `parent_dir_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Update table structure for `site_unknown` to include `dir_id` column
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
END//
DELIMITER ;
CALL manx_add_site_unknown_dir_id();
DROP PROCEDURE `manx_add_site_unknown_dir_id`;

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
-- Begin data modification
--
START TRANSACTION;

--
-- Populate remaining directory tree
--
CALL manx_unknown_directory_migrater();

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
DROP FUNCTION IF EXISTS `manx_parent_dir`;
