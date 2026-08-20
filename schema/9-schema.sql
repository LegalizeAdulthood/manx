--
-- Begin schema modification
--

--
-- Convert application tables from MyISAM to InnoDB.
--
-- Production migration path:
-- 1. Take and verify a full database backup.
-- 2. Schedule a maintenance window; ALTER TABLE copies table data and can
--    hold metadata locks while each table is converted.
-- 3. Apply this schema file after schema/8-schema.sql.
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
-- End schema modification
--

--
-- Migration cleanup
--
DROP PROCEDURE IF EXISTS `manx_convert_myisam_tables_to_innodb`;
