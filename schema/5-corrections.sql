--
-- update bitsavers mirrors
--
INSERT INTO `mirror`(`site`, `original_stem`, `copy_stem`, `rank`)
    VALUES (3, 'http://bitsavers.org/', 'http://bitsavers.informatik.uni-stuttgart.de/', 4),
        (3, 'http://bitsavers.org/', 'ftp://bitsavers.informatik.uni-stuttgart.de/', 3),
        (3, 'http://bitsavers.org/', 'ftp://ftp.mirrorservice.org/sites/www.bitsavers.org/', 2);
DELETE FROM `mirror` WHERE `mirror_id` = 5;

--
-- Correct locations of Richard Thomson's documents (site 52)
--

--
-- legalize_bitsavers_migrate_dup migrates a copy from legalize site to bitsavers
-- and eliminates duplication publication
--
DROP PROCEDURE IF EXISTS `legalize_bitsavers_migrate_dup`;
DELIMITER //
CREATE PROCEDURE `legalize_bitsavers_migrate_dup`(IN `old_copy` INT(11),
    IN `old_pub` INT(11),
    IN `new_copy` INT(11),
    IN `new_pub_history` INT(11),
    IN `new_pub` INT(11))
BEGIN
    -- delete non-existent copy
    DELETE FROM `copy` WHERE `copy_id` = `old_copy`;
    -- update pub to new pub_history
    UPDATE `pub` SET `pub_history` = `new_pub_history` WHERE `pub_id` = `old_pub`;
    -- update existing copy to correct pub
    UPDATE `copy` SET `pub` = `old_pub` WHERE `copy_id` = `new_copy`;
    -- update pub_history to correct pub
    UPDATE `pub_history` SET `ph_pub` = `old_pub` WHERE `ph_id` = `new_pub_history`;
    -- delete duplicate pub
    DELETE FROM `pub` WHERE `pub_id` = `new_pub`;
END//
DELIMITER ;
CALL `legalize_bitsavers_migrate_dup`(3550, 17446, 15406, 18458, 18466);
CALL `legalize_bitsavers_migrate_dup`(3549, 17445, 15407, 18459, 18467);
CALL `legalize_bitsavers_migrate_dup`(3548, 17444, 15408, 18460, 18468);
UPDATE `pub_history`
    SET `ph_revision` = 'Rev. A',
        `ph_pub_date` = '1983-11-30'
    WHERE `ph_id` = 18461;
CALL `legalize_bitsavers_migrate_dup`(3547, 17442, 15409, 18461, 18469);
CALL `legalize_bitsavers_migrate_dup`(3546, 17441, 15403, 18455, 18463);
DROP PROCEDURE IF EXISTS `legalize_bitsavers_migrate_dup`;

--
-- legalize_bitsavers_migrate_copy migrates a copy from legalize site to bitsavers
--
DROP PROCEDURE IF EXISTS `legalize_bitsavers_migrate_copy`;
DELIMITER //
CREATE PROCEDURE `legalize_bitsavers_migrate_copy`(IN `old_copy` INT(11),
    IN `subdir` VARCHAR(255))
BEGIN
    UPDATE `copy`
        SET `url` = CONCAT(
                CONCAT('http://bitsavers.org/pdf/', `subdir`),
                REVERSE(SUBSTRING_INDEX(REVERSE(`url`), '/', 1))),
            `site` = 3
        WHERE `copy_id` = `old_copy`;
END//
DELIMITER ;
CALL `legalize_bitsavers_migrate_copy`(10176, 'hazeltine/H2000/');
DROP PROCEDURE IF EXISTS `legalize_bitsavers_migrate_copy`;

-- delete old copy
DELETE FROM `copy` WHERE `copy_id` = 3537;
-- new copy is for old pub
UPDATE `copy` SET `pub` = 17432 WHERE `copy_id` = 15437;
-- delete new pub
DELETE FROM `pub` WHERE `pub_id` = 18496;
-- delete new pub_history
DELETE FROM `pub_history` WHERE `ph_id` = 18488;

-- delete duplicated pub, pub_history, copy
DELETE FROM `pub` WHERE `pub_id` = 16314;
DELETE FROM `pub_history` WHERE `ph_id` = 16311;
DELETE FROM `copy` WHERE `copy_id` = 3535;

--
-- delete orphaned copies
--
DELETE FROM `copy` WHERE `copy_id` = 3533;
DELETE FROM `copy` WHERE `copy_id` = 3534;
DELETE FROM `copy` WHERE `copy_id` = 3536;
DELETE FROM `copy` WHERE `copy_id` = 3539;
DELETE FROM `copy` WHERE `copy_id` = 3540;
DELETE FROM `copy` WHERE `copy_id` = 3541;
DELETE FROM `copy` WHERE `copy_id` = 3542;
DELETE FROM `copy` WHERE `copy_id` = 3543;
DELETE FROM `copy` WHERE `copy_id` = 3544;
DELETE FROM `copy` WHERE `copy_id` = 3545;

--
-- delete site
--
DELETE FROM `site` WHERE `site_id` = 52;

-- eliminate handle from scan credits
UPDATE `copy`
    SET `credits` = 'Scanned by Richard Thomson'
    WHERE `credits` = 'Scanned by legalize';
