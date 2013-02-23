--
-- Manx version 2.0.4
--
UPDATE `properties`
    SET `value` = '2.0.4'
    WHERE `name` = 'version';

--
-- Add new format extensions
--
INSERT INTO `format_extension`(`format`,`extension`) VALUES
    ('HTML', 'html.tgz'),
    ('HTML', 'htm.tgz'),
    ('Text', 'txt.Z');
