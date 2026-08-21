-- Manx schema 2.2.0

INSERT INTO `properties` (`name`, `value`)
VALUES ('pdf_metadata_max_bytes', '4194304')
ON DUPLICATE KEY UPDATE `value` = `value`;

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

    IF EXISTS (
        SELECT `normalized_url`
        FROM `tmp_copy_url_normalized`
        GROUP BY `normalized_url`
        HAVING COUNT(DISTINCT `url`) > 1
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'copy.url normalization would create duplicate URLs';
    END IF;

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

UPDATE `properties`
    SET `value` = '2.2.0'
    WHERE `name` = 'version';
