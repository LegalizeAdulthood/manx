-- Manx schema 2.2.0

INSERT INTO `properties` (`name`, `value`)
VALUES ('pdf_metadata_max_bytes', '4194304')
ON DUPLICATE KEY UPDATE `value` = `value`;

UPDATE `properties`
    SET `value` = '2.2.0'
    WHERE `name` = 'version';
