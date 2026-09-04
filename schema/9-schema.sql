-- Manx schema 2.2.0

INSERT INTO `properties` (`name`, `value`)
VALUES ('pdf_metadata_max_bytes', '4194304')
ON DUPLICATE KEY UPDATE `value` = `value`;

ALTER TABLE `site_unknown`
  ADD COLUMN `pdf_title` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_keywords` VARCHAR(100) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_abstract` VARCHAR(2048) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_notes` VARCHAR(200) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_credits` VARCHAR(200) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_metadata_status` VARCHAR(16) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_metadata_error` VARCHAR(255) NOT NULL DEFAULT '',
  ADD COLUMN `pdf_metadata_checked` DATETIME NULL DEFAULT NULL;

DROP PROCEDURE IF EXISTS `manx_encode_url_path`;
DELIMITER //
CREATE PROCEDURE `manx_encode_url_path`(
    IN `source_path` VARCHAR(1024),
    OUT `encoded_path` VARCHAR(1024))
BEGIN
    DECLARE `i` INT DEFAULT 1;
    DECLARE `path_len` INT DEFAULT 0;
    DECLARE `ch` CHAR(1) DEFAULT '';

    SET `encoded_path` = '';
    SET `path_len` = LENGTH(`source_path`);
    WHILE `i` <= `path_len` DO
        SET `ch` = SUBSTRING(`source_path`, `i`, 1);
        IF `ch` = '%'
            AND `i` + 2 <= `path_len`
            AND SUBSTRING(`source_path`, `i` + 1, 2)
                REGEXP '^[0-9A-Fa-f][0-9A-Fa-f]$' THEN
            SET `encoded_path` = CONCAT(`encoded_path`, '%',
                UPPER(SUBSTRING(`source_path`, `i` + 1, 2)));
            SET `i` = `i` + 3;
        ELSEIF `ch` REGEXP '^[-A-Za-z0-9._~/]$' THEN
            SET `encoded_path` = CONCAT(`encoded_path`, `ch`);
            SET `i` = `i` + 1;
        ELSE
            SET `encoded_path` = CONCAT(`encoded_path`, '%',
                LPAD(HEX(ASCII(`ch`)), 2, '0'));
            SET `i` = `i` + 1;
        END IF;
    END WHILE;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS `manx_normalize_copy_url`;
DELIMITER //
CREATE PROCEDURE `manx_normalize_copy_url`(
    IN `source_url` VARCHAR(255),
    OUT `normalized_url` VARCHAR(1024))
BEGIN
    DECLARE `working_url` VARCHAR(255) DEFAULT '';
    DECLARE `plus_prefix` VARCHAR(1) DEFAULT '';
    DECLARE `query_pos` INT DEFAULT 0;
    DECLARE `scheme_pos` INT DEFAULT 0;
    DECLARE `path_pos` INT DEFAULT 0;
    DECLARE `location_part` VARCHAR(255) DEFAULT '';
    DECLARE `query_part` VARCHAR(255) DEFAULT '';
    DECLARE `prefix_part` VARCHAR(255) DEFAULT '';
    DECLARE `path_part` VARCHAR(255) DEFAULT '';
    DECLARE `encoded_path` VARCHAR(1024) DEFAULT '';

    IF `source_url` IS NULL THEN
        SET `normalized_url` = NULL;
    ELSE
        SET `working_url` = `source_url`;
        IF LEFT(`working_url`, 1) = '+' THEN
            SET `plus_prefix` = '+';
            SET `working_url` = SUBSTRING(`working_url`, 2);
        END IF;

        SET `query_pos` = LOCATE('?', `working_url`);
        IF `query_pos` > 0 THEN
            SET `location_part` = SUBSTRING(`working_url`, 1, `query_pos` - 1);
            SET `query_part` = SUBSTRING(`working_url`, `query_pos`);
        ELSE
            SET `location_part` = `working_url`;
            SET `query_part` = '';
        END IF;

        IF LEFT(`location_part`, 2) = '//' THEN
            SET `path_pos` = LOCATE('/', `location_part`, 3);
        ELSE
            SET `scheme_pos` = LOCATE('://', `location_part`);
            IF `scheme_pos` > 0 THEN
                SET `path_pos` = LOCATE('/', `location_part`, `scheme_pos` + 3);
            ELSE
                SET `path_pos` = 1;
            END IF;
        END IF;

        IF `path_pos` = 0 THEN
            SET `normalized_url` = CONCAT(`plus_prefix`, `location_part`,
                `query_part`);
        ELSEIF `path_pos` = 1 AND `scheme_pos` = 0
            AND LEFT(`location_part`, 2) <> '//' THEN
            CALL `manx_encode_url_path`(`location_part`, `encoded_path`);
            SET `normalized_url` = CONCAT(`plus_prefix`, `encoded_path`,
                `query_part`);
        ELSE
            SET `prefix_part` = SUBSTRING(`location_part`, 1, `path_pos` - 1);
            SET `path_part` = SUBSTRING(`location_part`, `path_pos`);
            CALL `manx_encode_url_path`(`path_part`, `encoded_path`);
            SET `normalized_url` = CONCAT(`plus_prefix`, `prefix_part`,
                `encoded_path`, `query_part`);
        END IF;
    END IF;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS `manx_normalize_copy_urls`;
DELIMITER //
CREATE PROCEDURE `manx_normalize_copy_urls`()
BEGIN
    DECLARE `done` INT DEFAULT 0;
    DECLARE `current_copy_id` INT DEFAULT 0;
    DECLARE `current_url` VARCHAR(255) DEFAULT '';
    DECLARE `normalized_url` VARCHAR(1024) DEFAULT '';
    DECLARE `copy_urls` CURSOR FOR
        SELECT `copy_id`, `url` FROM `copy` WHERE `url` IS NOT NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET `done` = 1;

    DROP TEMPORARY TABLE IF EXISTS `tmp_copy_url_normalized`;
    CREATE TEMPORARY TABLE `tmp_copy_url_normalized`(
        `copy_id` INT(11) NOT NULL,
        `url` VARCHAR(255) NOT NULL,
        `normalized_url` VARCHAR(1024) NOT NULL,
        PRIMARY KEY (`copy_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    OPEN `copy_urls`;
    copy_loop: LOOP
        FETCH `copy_urls` INTO `current_copy_id`, `current_url`;
        IF `done` = 1 THEN
            LEAVE copy_loop;
        END IF;

        CALL `manx_normalize_copy_url`(`current_url`, `normalized_url`);
        INSERT INTO `tmp_copy_url_normalized`
            (`copy_id`, `url`, `normalized_url`)
            VALUES (`current_copy_id`, `current_url`, `normalized_url`);
    END LOOP;
    CLOSE `copy_urls`;

    UPDATE `copy` `c`, `tmp_copy_url_normalized` `n`
        SET `c`.`url` = `n`.`normalized_url`
        WHERE `c`.`copy_id` = `n`.`copy_id`
        AND `c`.`url` <> `n`.`normalized_url`;

    DROP TEMPORARY TABLE IF EXISTS `tmp_copy_url_normalized`;
END//
DELIMITER ;

CALL `manx_normalize_copy_urls`();
DROP PROCEDURE IF EXISTS `manx_normalize_copy_urls`;
DROP PROCEDURE IF EXISTS `manx_normalize_copy_url`;
DROP PROCEDURE IF EXISTS `manx_encode_url_path`;

DROP PROCEDURE IF EXISTS `manx_upgrade_site_urls_to_https`;
DELIMITER //
CREATE PROCEDURE `manx_upgrade_site_urls_to_https`()
BEGIN
    UPDATE `site`
        SET `url` = CONCAT('https://', SUBSTRING(`url`, 8))
        WHERE `url` LIKE 'http://%';

    UPDATE `site`
        SET `copy_base` = CONCAT('https://', SUBSTRING(`copy_base`, 8))
        WHERE `copy_base` LIKE 'http://%';

    DROP TEMPORARY TABLE IF EXISTS `tmp_https_copy_base_sites`;
    CREATE TEMPORARY TABLE `tmp_https_copy_base_sites`(
        `site_id` INT(11) NOT NULL,
        `copy_base` VARCHAR(200) NOT NULL,
        `http_copy_base` VARCHAR(200) NOT NULL,
        PRIMARY KEY (`site_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    INSERT INTO `tmp_https_copy_base_sites`
        (`site_id`, `copy_base`, `http_copy_base`)
        SELECT `site_id`, `copy_base`,
            CONCAT('http://', SUBSTRING(`copy_base`, 9))
        FROM `site`
        WHERE `copy_base` LIKE 'https://%';

    UPDATE `copy` `c`, `tmp_https_copy_base_sites` `h`
        SET `c`.`url` = CONCAT(`h`.`copy_base`,
            SUBSTRING(`c`.`url`, CHAR_LENGTH(`h`.`http_copy_base`) + 1))
        WHERE `c`.`site` = `h`.`site_id`
        AND LEFT(`c`.`url`, CHAR_LENGTH(`h`.`http_copy_base`))
            = `h`.`http_copy_base`;

    DROP TEMPORARY TABLE IF EXISTS `tmp_https_copy_base_sites`;
END//
DELIMITER ;

CALL `manx_upgrade_site_urls_to_https`();
DROP PROCEDURE IF EXISTS `manx_upgrade_site_urls_to_https`;

ALTER TABLE `site_unknown_dir`
  ALTER COLUMN `part_regex`
  SET DEFAULT '^([^_]*[0-9][0-9][^_]*)_';

DROP PROCEDURE IF EXISTS `manx_backfill_site_unknown_dir_part_regex`;
DELIMITER //
CREATE PROCEDURE `manx_backfill_site_unknown_dir_part_regex`()
BEGIN
    UPDATE `site_unknown_dir`
        SET `part_regex` = '^([^_]*[0-9][0-9][^_]*)_'
        WHERE `part_regex` = '';
END//
DELIMITER ;

CALL `manx_backfill_site_unknown_dir_part_regex`();
DROP PROCEDURE IF EXISTS `manx_backfill_site_unknown_dir_part_regex`;

ALTER TABLE `site_unknown`
  ADD KEY `dir_id` (`dir_id`),
  ADD KEY `site_ignored_dir` (`site_id`, `ignored`, `dir_id`);

ALTER TABLE `site_unknown_dir`
  ADD KEY `parent_dir_id` (`parent_dir_id`);

DROP PROCEDURE IF EXISTS `manx_purge_su_copies`;
DELIMITER //
CREATE PROCEDURE `manx_purge_su_copies`(
    IN `site_name` VARCHAR(100))
BEGIN
    DECLARE `target_site_id` INT DEFAULT -1;
    DECLARE `site_copy_base` VARCHAR(200) DEFAULT '';

    SELECT COALESCE(MAX(`site_id`), -1),
            COALESCE(MAX(`copy_base`), '')
        INTO `target_site_id`, `site_copy_base`
        FROM `site`
        WHERE `name` = `site_name`;

    IF `target_site_id` <> -1 THEN
        DELETE `su`
        FROM `site_unknown` `su`
            LEFT JOIN `site_unknown_dir` `sud`
                ON `sud`.`site_id` = `target_site_id`
                AND `sud`.`id` = `su`.`dir_id`
            JOIN `copy` `c` FORCE INDEX (`site_filename`)
                ON `c`.`site` = `target_site_id`
                AND `c`.`filename` = `su`.`filename`
                AND `c`.`url` = IF(`su`.`dir_id` = -1,
                    CONCAT(`site_copy_base`, `su`.`filename`),
                    CONCAT(`site_copy_base`, `sud`.`path`, '/',
                        `su`.`filename`))
        WHERE `su`.`site_id` = `target_site_id`;
    END IF;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS `manx_reset_partial_unknown_ingest`;
DELIMITER //
CREATE PROCEDURE `manx_reset_partial_unknown_ingest`(
    IN `site_name` VARCHAR(100))
BEGIN
    DECLARE `target_site_id` INT DEFAULT -1;

    SELECT COALESCE(MAX(`site_id`), -1) INTO `target_site_id`
        FROM `site`
        WHERE `name` = `site_name`;

    IF `target_site_id` <> -1 THEN
        UPDATE `site_unknown` `su`
            JOIN `site` `s`
                ON `s`.`site_id` = `su`.`site_id`
            JOIN `site_unknown_dir` `sud`
                ON `sud`.`site_id` = `su`.`site_id`
                AND `sud`.`id` = `su`.`dir_id`
            JOIN `site_company_dir` `scd`
                ON `scd`.`site_id` = `su`.`site_id`
            LEFT JOIN `copy` `c` FORCE INDEX (`site_filename`)
                ON `c`.`site` = `target_site_id`
                AND `c`.`filename` = `su`.`filename`
                AND `c`.`url` = CONCAT(`s`.`copy_base`, `sud`.`path`, '/',
                    `su`.`filename`)
            SET `su`.`scanned` = 0
            WHERE `su`.`site_id` = `target_site_id`
            AND `s`.`name` = `site_name`
            AND `s`.`live` = 'Y'
            AND `su`.`scanned` = 1
            AND `su`.`ignored` = 0
            AND `c`.`copy_id` IS NULL
            AND INSTR(`su`.`filename`, '#') = 0
            AND INSTR(`su`.`filename`, ' ') = 0
            AND INSTR(`su`.`filename`, '&') = 0
            AND INSTR(`su`.`filename`, '%') = 0
            AND `su`.`filename` LIKE '%\_%\_%.pdf'
            AND (
                (`scd`.`parent_directory` = ''
                    AND `sud`.`path` LIKE CONCAT(`scd`.`directory`, '/%'))
                OR
                (`scd`.`parent_directory` <> ''
                    AND `sud`.`path` LIKE CONCAT(`scd`.`parent_directory`,
                        '/', `scd`.`directory`, '/%'))
            );
    END IF;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS `manx_purge_unused_unknown_directories`;
DELIMITER //
CREATE PROCEDURE `manx_purge_unused_unknown_directories`(
    IN `site_name` VARCHAR(100))
BEGIN
    DECLARE `deleted_count` INT DEFAULT 1;
    DECLARE `target_site_id` INT DEFAULT -1;

    SELECT COALESCE(MAX(`site_id`), -1) INTO `target_site_id`
        FROM `site`
        WHERE `name` = `site_name`;

    IF `target_site_id` <> -1 THEN
        WHILE `deleted_count` > 0 DO
            DELETE `d` FROM `site_unknown_dir` `d`
                LEFT JOIN `site_unknown` `su`
                    ON `su`.`site_id` = `target_site_id`
                    AND `su`.`dir_id` = `d`.`id`
                LEFT JOIN `site_unknown_dir` `child`
                    ON `child`.`site_id` = `target_site_id`
                    AND `child`.`parent_dir_id` = `d`.`id`
                WHERE `d`.`site_id` = `target_site_id`
                AND `su`.`id` IS NULL
                AND `child`.`id` IS NULL;
            SET `deleted_count` = ROW_COUNT();
        END WHILE;
    END IF;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS `manx_update_unknown_dir_ignored`;
DELIMITER //
CREATE PROCEDURE `manx_update_unknown_dir_ignored`(
    IN `site_name` VARCHAR(100))
BEGIN
    DECLARE `target_site_id` INT DEFAULT -1;

    DROP TEMPORARY TABLE IF EXISTS `tmp_dir_ids_not_ignored`;
    CREATE TEMPORARY TABLE `tmp_dir_ids_not_ignored`(
        `id` INT(11) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    DROP TEMPORARY TABLE IF EXISTS `tmp_dir_ids_to_process`;
    CREATE TEMPORARY TABLE `tmp_dir_ids_to_process`(
        `id` INT(11) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    DROP TEMPORARY TABLE IF EXISTS `tmp_dir_ids_next`;
    CREATE TEMPORARY TABLE `tmp_dir_ids_next`(
        `id` INT(11) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    SELECT COALESCE(MAX(`site_id`), -1) INTO `target_site_id`
        FROM `site`
        WHERE `name` = `site_name`;

    IF `target_site_id` <> -1 THEN
        INSERT IGNORE INTO `tmp_dir_ids_not_ignored`
            SELECT DISTINCT `dir_id`
            FROM `site_unknown`
            WHERE `site_id` = `target_site_id`
            AND `ignored` = 0
            AND `dir_id` <> -1;
        INSERT IGNORE INTO `tmp_dir_ids_to_process`
            SELECT `id` FROM `tmp_dir_ids_not_ignored`;

        WHILE (SELECT COUNT(*) FROM `tmp_dir_ids_to_process`) > 0 DO
            DELETE FROM `tmp_dir_ids_next`;
            INSERT IGNORE INTO `tmp_dir_ids_next`
                SELECT DISTINCT `sud`.`parent_dir_id`
                FROM `site_unknown_dir` `sud`,
                    `tmp_dir_ids_to_process` `tdi`
                WHERE `sud`.`site_id` = `target_site_id`
                AND `sud`.`id` = `tdi`.`id`
                AND `sud`.`parent_dir_id` <> -1;
            DELETE FROM `tmp_dir_ids_next`
                WHERE `id` IN (SELECT `id` FROM `tmp_dir_ids_not_ignored`);
            INSERT IGNORE INTO `tmp_dir_ids_not_ignored`
                SELECT `id` FROM `tmp_dir_ids_next`;
            DELETE FROM `tmp_dir_ids_to_process`;
            INSERT IGNORE INTO `tmp_dir_ids_to_process`
                SELECT `id` FROM `tmp_dir_ids_next`;
        END WHILE;

        UPDATE `site_unknown_dir` `d`
            LEFT JOIN `tmp_dir_ids_not_ignored` `tdi`
                ON `tdi`.`id` = `d`.`id`
            SET `d`.`ignored` = IF(`tdi`.`id` IS NULL, 1, 0)
            WHERE `d`.`site_id` = `target_site_id`;
    END IF;

    DROP TEMPORARY TABLE IF EXISTS `tmp_dir_ids_next`;
    DROP TEMPORARY TABLE IF EXISTS `tmp_dir_ids_to_process`;
    DROP TEMPORARY TABLE IF EXISTS `tmp_dir_ids_not_ignored`;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS `manx_cleanup_unknown_dir`;
DELIMITER //
CREATE PROCEDURE `manx_cleanup_unknown_dir`(
    `sud_id` INT(11))
BEGIN
    DECLARE `current_id` INT DEFAULT -1;
    DECLARE `parent_id` INT DEFAULT -1;
    DECLARE `dir_exists` INT DEFAULT 0;
    DECLARE `file_count` INT DEFAULT 0;
    DECLARE `child_count` INT DEFAULT 0;
    DECLARE `unignored_file_count` INT DEFAULT 0;
    DECLARE `unignored_child_count` INT DEFAULT 0;

    SET `current_id` = `sud_id`;

    purge_loop: WHILE `current_id` <> -1 DO
        SELECT COUNT(*), COALESCE(MAX(`parent_dir_id`), -1)
            INTO `dir_exists`, `parent_id`
            FROM `site_unknown_dir`
            WHERE `id` = `current_id`;

        IF `dir_exists` = 0 THEN
            SET `current_id` = -1;
        ELSE
            SELECT COUNT(*) INTO `file_count`
                FROM `site_unknown`
                WHERE `dir_id` = `current_id`;
            SELECT COUNT(*) INTO `child_count`
                FROM `site_unknown_dir`
                WHERE `parent_dir_id` = `current_id`;

            IF `file_count` = 0 AND `child_count` = 0 THEN
                DELETE FROM `site_unknown_dir` WHERE `id` = `current_id`;
                SET `current_id` = `parent_id`;
            ELSE
                LEAVE purge_loop;
            END IF;
        END IF;
    END WHILE;

    update_loop: WHILE `current_id` <> -1 DO
        SELECT COUNT(*), COALESCE(MAX(`parent_dir_id`), -1)
            INTO `dir_exists`, `parent_id`
            FROM `site_unknown_dir`
            WHERE `id` = `current_id`;

        IF `dir_exists` = 0 THEN
            SET `current_id` = -1;
        ELSE
            SELECT COUNT(*) INTO `unignored_file_count`
                FROM `site_unknown`
                WHERE `dir_id` = `current_id`
                AND `ignored` = 0;
            SELECT COUNT(*) INTO `unignored_child_count`
                FROM `site_unknown_dir`
                WHERE `parent_dir_id` = `current_id`
                AND `ignored` = 0;

            UPDATE `site_unknown_dir`
                SET `ignored` = IF(
                    `unignored_file_count` = 0
                    AND `unignored_child_count` = 0,
                    1, 0)
                WHERE `id` = `current_id`;
            SET `current_id` = `parent_id`;
        END IF;
    END WHILE;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS `manx_update_unknown_single_dir_ignored`;
DELIMITER //
CREATE PROCEDURE `manx_update_unknown_single_dir_ignored`(
    `su_id` INT(11))
BEGIN
    DECLARE `cleanup_dir_id` INT DEFAULT -1;

    SELECT COALESCE(MAX(`dir_id`), -1) INTO `cleanup_dir_id`
        FROM `site_unknown`
        WHERE `id` = `su_id`;
    CALL `manx_cleanup_unknown_dir`(`cleanup_dir_id`);
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS `manx_cleanup_all_unknown_dirs`;
DELIMITER //
CREATE PROCEDURE `manx_cleanup_all_unknown_dirs`()
BEGIN
    DECLARE `done` INT DEFAULT 0;
    DECLARE `current_site_name` VARCHAR(100) DEFAULT '';
    DECLARE `site_names` CURSOR FOR
        SELECT `name` FROM `site` WHERE `name` IS NOT NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET `done` = 1;

    OPEN `site_names`;
    site_loop: LOOP
        FETCH `site_names` INTO `current_site_name`;
        IF `done` = 1 THEN
            LEAVE site_loop;
        END IF;

        CALL `manx_purge_su_copies`(`current_site_name`);
        CALL `manx_reset_partial_unknown_ingest`(`current_site_name`);
        CALL `manx_purge_unused_unknown_directories`(`current_site_name`);
        CALL `manx_update_unknown_dir_ignored`(`current_site_name`);
    END LOOP;
    CLOSE `site_names`;
END//
DELIMITER ;

CALL `manx_cleanup_all_unknown_dirs`();
DROP PROCEDURE IF EXISTS `manx_cleanup_all_unknown_dirs`;
DROP PROCEDURE IF EXISTS `manx_reset_partial_unknown_ingest`;

UPDATE `properties`
    SET `value` = '2.2.0'
    WHERE `name` = 'version';
