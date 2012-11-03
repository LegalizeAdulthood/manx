--
-- Table structure for table `properties`
--

CREATE TABLE IF NOT EXISTS `properties` (
  `name` VARCHAR(255) NOT NULL,
  `value` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table `company_bitsavers`
--

CREATE TABLE IF NOT EXISTS `company_bitsavers` (
  `company_id` INT(11) NOT NULL AUTO_INCREMENT,
  `directory` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`company_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table `format_extension`
--

CREATE TABLE IF NOT EXISTS `format_extension` (
  `format` VARCHAR(10) NOT NULL DEFAULT '',
  `extension` VARCHAR(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`format`, `extension`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table `bitsavers_unknown`
--

CREATE TABLE IF NOT EXISTS `bitsavers_unknown` (
  `path` VARCHAR(255) NOT NULL,
  `ignored` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`path`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Schema version 2
--
INSERT INTO `properties` (`name`,`value`) VALUES ('version', '2');

--
-- BitSavers directories
--
INSERT INTO `company_bitsavers`(`company_id`,`directory`) VALUES
    (71,  '3Com'),
    (73,  'able'),
    (98,  'acorn'),
    (53,  'adaptec'),
    (87,  'advancedDigitalCorp'),
    (56,  'amd'),
    (54,  'asi'),
    (22,  'alphaMicrosystems'),
    (101, 'altos'),
    (75,  'analogic'),
    (25,  'apollo'),
    (46,  'apple'),
    (24,  'adds'),
    (61,  'appliedMicrosystems'),
    (62,  'archive'),
    (20,  'att'),
    (74,  'beehive'),
    (21,  'burroughs'),
    (93,  'calcomp'),
    (92,  'ccs'),
    (76,  'centralData'),
    (17,  'centronics'),
    (77,  'centuryData'),
    (60,  'compupro'),
    (32,  'cdc'),
    (63,  'corvus'),
    (36,  'cromemco'),
    (10,  'dg'),
    (45,  'dataIO'),
    (79,  'dsd'),
    (18,  'datapoint'),
    (38,  'dataproducts'),
    (96,  'diablo'),
    (1,   'dec'),
    (44,  'digitalResearch'),
    (52,  'digitronics'),
    (97,  'dilog'),
    (104, 'documation'),
    (95,  'dualSystems'),
    (64,  'emulex'),
    (27,  'epson'),
    (51,  'facit'),
    (50,  'ferranti'),
    (80,  'gri'),
    (84,  'hayes'),
    (14,  'hazeltine'),
    (7,   'hp'),
    (57,  'honeywell'),
    (65,  'inmos'),
    (47,  'intel'),
    (58,  'interdata'),
    (19,  'ibm'),
    (100, 'ict_icl'),
    (59,  'fluke'),
    (81,  'megatek'),
    (39,  'mits'),
    (68,  'morrow'),
    (49,  'motorola'),
    (30,  'national'),
    (85,  'osborne'),
    (31,  'prime'),
    (78,  'qume'),
    (82,  'randomCorporation'),
    (15,  'researchInc'),
    (86,  'seattleComputer'),
    (69,  'shugart'),
    (16,  'soroc'),
    (26,  'sun'),
    (5,   'tektronix'),
    (70,  'teletype'),
    (6,   'televideo'),
    (2,   'ti'),
    (23,  'westernDigital'),
    (102, 'xebec'),
    (48,  'xerox'),
    (40,  'zilog');

--
-- Format extensions
--
INSERT INTO `format_extension`(`format`,`extension`) VALUES
    ('GIF', 'gif'),
    ('HTML', 'html'),
    ('HTML', 'htm'),
    ('JPEG', 'jpeg'),
    ('JPEG', 'jpg'),
    ('PDF', 'pdf'),
    ('PNG', 'png'),
    ('PostScript', 'ps'),
    ('Text', 'txt'),
    ('TIFF', 'tif'),
    ('TIFF', 'tiff');

--
-- Change UPPERCASE tables to lower_case
--

-- CITEPUB
ALTER TABLE `CITEPUB` RENAME TO `cite_pub`;

-- COMPANY
ALTER TABLE `COMPANY` RENAME TO `company`;
ALTER TABLE `company`
    CHANGE COLUMN `shortname`
        `short_name` VARCHAR(50) NULL DEFAULT NULL;

-- COPY
ALTER TABLE `COPY` RENAME TO `copy`;
ALTER TABLE `copy`
    CHANGE COLUMN `copyid`
        `copy_id` INT(11) NOT NULL AUTO_INCREMENT;

-- LANGUAGE
ALTER TABLE `LANGUAGE` RENAME TO `language`;

-- PART
ALTER TABLE `PART` RENAME TO `part`;

-- PUB
ALTER TABLE `PUB` RENAME TO `pub`;

-- PUBHISTORY
ALTER TABLE `PUBHISTORY` RENAME TO `pub_history`;
ALTER TABLE `pub_history`
    CHANGE COLUMN `ph_pubtype`
        `ph_pub_type` CHAR(1) NOT NULL DEFAULT 'D',
    CHANGE COLUMN `ph_pubdate`
        `ph_pub_date` VARCHAR(10) NULL DEFAULT NULL,
    CHANGE COLUMN `ph_abstract`
        `ph_abstract` VARCHAR(2048) NULL DEFAULT NULL;

-- PUBTAG
ALTER TABLE `PUBTAG` RENAME TO `pub_tag`;

-- SITE
ALTER TABLE `SITE` RENAME TO `site`;

-- SUPERSESSION
ALTER TABLE `SUPERSESSION` RENAME TO `supersession`;

-- TAG
ALTER TABLE `TAG` RENAME TO `tag`;

-- TOC
ALTER TABLE `TOC` RENAME TO `toc`;

COMMIT;
