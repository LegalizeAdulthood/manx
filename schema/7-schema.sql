--
-- Table structure for table `site_company_dir`
--

DROP TABLE IF EXISTS `site_company_dir`;
CREATE TABLE IF NOT EXISTS `site_company_dir` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `site_id` INT(11) NOT NULL,
  `company_id` INT(11) NOT NULL,
  `directory` VARCHAR(255) NOT NULL DEFAULT '',
  `parent_directory` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE (`site_id`, `company_id`, `directory`(128), `parent_directory`(128))
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- site company directories
--
INSERT INTO `site_company_dir`(`site_id`, `company_id`,`directory`)
  SELECT s.`site_id`, bs.`company_id`, bs.`directory`
  FROM `site` s, `company_bitsavers` bs
  WHERE s.`name` = 'bitsavers';
INSERT INTO `site_company_dir`(`site_id`, `company_id`, `directory`, `parent_directory`)
  SELECT s.`site_id`, cc.`company_id`, cc.`directory`, 'computing'
  FROM `site` s, `company_chiclassiccomp` cc
  WHERE s.`name` = 'ChiClassicComp';
DELETE FROM `company_bitsavers`;
DELETE FROM `company_chiclassiccomp`;
DROP TABLE IF EXISTS `company_bitsavers`;
DROP TABLE IF EXISTS `company_chiclassiccomp`;

--
-- Table structure for table `site_unknown`
--

DROP TABLE IF EXISTS `site_unknown`;
CREATE TABLE IF NOT EXISTS `site_unknown` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `site_id` INT(11) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `ignored` TINYINT(1) NOT NULL DEFAULT 0,
  `scanned` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`site_id`, `path`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- site unknown paths
--
INSERT INTO `site_unknown`(`site_id`, `path`, `ignored`)
  SELECT s.`site_id`, bs.`path`, bs.`ignored`
  FROM `site` s, `bitsavers_unknown` bs
  WHERE s.`name` = 'bitsavers';
INSERT INTO `site_unknown`(`site_id`, `path`, `ignored`)
  SELECT s.`site_id`, bs.`path`, bs.`ignored`
  FROM `site` s, `chiclassiccomp_unknown` bs
  WHERE s.`name` = 'ChiClassicComp';
DELETE FROM `bitsavers_unknown`;
DELETE FROM `chiclassiccomp_unknown`;
DROP TABLE IF EXISTS `bitsavers_unknown`;
DROP TABLE IF EXISTS `chiclassiccomp_unknown`;

--
-- Automatic ingestion user
--
INSERT INTO `user`(`email`,`first_name`,`last_name`) VALUES ('ingestion@manx-docs.org', 'Ingestion', 'Robot');


--
-- Misspelled IndexByDate.txt property
--
DELETE FROM `properties` WHERE `name` = 'chiclassiccmp_whats_new_timestamp';

--
-- Manx version 2.0.7
--
UPDATE `properties`
    SET `value` = '2.0.7'
    WHERE `name` = 'version';

COMMIT;
