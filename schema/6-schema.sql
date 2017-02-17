--
-- Table structure for table `company_chiclassiccomp`
--

DROP TABLE IF EXISTS `company_chiclassiccmp`;
CREATE TABLE IF NOT EXISTS `company_chiclassiccomp` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) NOT NULL,
  `directory` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`company_id`),
  UNIQUE KEY (`directory`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table `chiclassiccomp_unknown`
--

DROP TABLE IF EXISTS `chiclassiccmp_unknown`;
CREATE TABLE IF NOT EXISTS `chiclassiccomp_unknown` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `path` VARCHAR(255) NOT NULL,
  `ignored` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`path`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- chiclassiccomp directories
--
INSERT INTO `company_chiclassiccomp`(`company_id`,`directory`) VALUES
    (101, 'Altos'),
    (120, 'AnnArborTerminals'),
    (46,  'Apple'),
    (20,  'AT&T'),
    (150, 'BellLabs'),
    (110, 'CadoSystems'),
    (92,  'CaliforniaComputerSystems'),
    (17,  'Centronics'),
    (60,  'CompuPro'),
    (32,  'CDC'),
    (63,  'Corvus'),
    (1,   'DEC'),
    (44,  'DigitalResearch'),
    (27,  'Epson'),
    (84,  'Hayes'),
    (12,  'Heathkit'),
    (7,   'HP'),
    (57,  'Honeywell'),
    (19,  'IBM'),
    (39,  'MITS'),
    (68,  'Morrow'),
    (49,  'Motorola'),
    (85,  'Osborne'),
    (31,  'Prime'),
    (26,  'Sun'),
    (5,   'Tektronix'),
    (70,  'Teletype'),
    (6,   'Televideo'),
    (2,   'TI'),
    (48,  'Xerox'),
    (40,  'Zilog');

--
-- Manx version 2.0.5
--
UPDATE `properties`
    SET `value` = '2.0.6'
    WHERE `name` = 'version';

COMMIT;
