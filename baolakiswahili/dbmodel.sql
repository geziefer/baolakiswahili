
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- BaoLaKiswahili implementation : © <Alexander Rühl> <alex@geziefer.de>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- create game board with fields and containing stones for each player
CREATE TABLE IF NOT EXISTS `board` ( 
    `player` INT UNSIGNED NOT NULL, 
    `field` TINYINT UNSIGNED NOT NULL, 
    `stones` TINYINT UNSIGNED NOT NULL, 
    PRIMARY KEY (`player`, `field`)
) ENGINE = InnoDB;
