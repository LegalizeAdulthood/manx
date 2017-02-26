--
-- Table structure for table `site_unknown_dir`
--

DROP TABLE IF EXISTS `site_unknown_dir`;
CREATE TABLE IF NOT EXISTS `site_unknown_dir` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `site_id` INT(11) NOT NULL,
  `directory` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`site_id`, `directory`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

---
--- Alter site_unknown table to hold filename and directory id
---
ALTER TABLE `site_unknown`
  ADD COLUMN `site_unknown_dir_id` INT(11) NOT NULL DEFAULT -1;
ALTER TABLE `site_unknown`
  ADD COLUMN `file_name` VARCHAR(255) NOT NULL DEFAULT '';

--
-- Populate site_unknown_dir with unknown path directories
--
INSERT INTO `site_unknown_dir`(`site_id`, `directory`)
  SELECT DISTINCT
    `site_id`,
    LEFT(`path`, LENGTH(`path`)-LENGTH(SUBSTRING_INDEX(REVERSE(`path`), '/', 1))-1) as `directory`
    FROM `site_unknown`;

--
-- Populate site_unknown_dir_id and file_name
--
UPDATE `site_unknown`
  SET
    `site_unknown_dir_id` = (
      SELECT `id` as `site_unknown_dir_id` FROM `site_unknown_dir`
        WHERE `directory`=LEFT(`path`, LENGTH(`path`)-LENGTH(SUBSTRING_INDEX(REVERSE(`path`), '/', 1))-1)
    ),
    `file_name` = REVERSE(SUBSTRING_INDEX(REVERSE(`path`), '/', 1));

--
-- Alter site_unknown to drop the path
--
ALTER TABLE `site_unknown`
  DROP COLUMN `path`;

--
-- Manx version 2.0.8
--
UPDATE `properties`
    SET `value` = '2.0.8'
    WHERE `name` = 'version';

COMMIT;
