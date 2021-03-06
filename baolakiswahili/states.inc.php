<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BaoLaKiswahili implementation : © <Alexander Rühl> <alex@geziefer.de>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */


$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => 2)
    ),

    // game variant selection
    2 => array(
        "name" => "variantSelect",
        "description" => "",
        "type" => "game",
        "action" => "stVariantSelect",
        "transitions" => array("playKiswahili" => 10, "playKujifunza" => 20, "playHus" => 30)
    ),

    // First phase of kiswahili type. Player selects a bowl, then a direction
    10 => array(
        "name" => "kunamuaMoveSelection",
        "description" => clienttranslate('${actplayer} must place a seed'),
        "descriptionmyturn" => clienttranslate('${you} must place a seed'),
        "type" => "activeplayer",
        "args" => "argKunamuaMoveSelection",
        "possibleactions" => array("selectMove"),
        "transitions" => array("selectMove" => 11, "zombiePass" => 11)
    ),

    // First phase game checks situation if next player is on, a special move oocurs, or one player wins
    11 => array(
        "name" => "kunamuaMoveExecution",
        "description" => clienttranslate('Move of ${actplayer} gets executed'),
        "descriptionmyturn" => clienttranslate('Your move gets executed'),
        "type" => "game",
        "action" => "stKunamuaNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array("nextPlayer" => 10, "decideNyumba" => 12, "selectTax" => 13, "switchPhase" => 20, "endGame" => 99)
    ),
    
    // First phase decision of making safari
    12 => array(
        "name" => "safariDecision",
        "description" => clienttranslate('${actplayer} must decide about safari'),
        "descriptionmyturn" => clienttranslate('${you} must decide about safari'),
        "type" => "activeplayer",
        "args" => "argSafariDecision",
        "possibleactions" => array("continueMove"),
        "transitions" => array("continueMove" => 11, "zombiePass" => 11)
    ),

    // First phase special move tax nyumba
    13 => array(
        "name" => "nyumbaTaxSelection",
        "description" => clienttranslate('${actplayer} must tax the nyumba'),
        "descriptionmyturn" => clienttranslate('${you} must tax the nyumba'),
        "type" => "activeplayer",
        "args" => "argNyumbaTaxSelection",
        "possibleactions" => array("taxNyumba"),
        "transitions" => array("taxNyumba" => 11, "zombiePass" => 11)
    ),

    // Second phase of kiswahili type or kujifunza type. Player selects a bowl, then a direction
    20 => array(
        "name" => "mtajiMoveSelection",
        "description" => clienttranslate('${actplayer} must make a move'),
        "descriptionmyturn" => clienttranslate('${you} must make a move'),
        "type" => "activeplayer",
        "args" => "argMtajiMoveSelection",
        "possibleactions" => array("selectMove"),
        "transitions" => array("selectMove" => 21, "zombiePass" => 21)
    ),

    // Second phase game checks situation if next player is on, a special move oocurs, or one player wins
    21 => array(
        "name" => "mtajiMoveExecution",
        "description" => clienttranslate('Move of ${actplayer} gets executed'),
        "descriptionmyturn" => clienttranslate('Your move gets executed'),
        "type" => "game",
        "action" => "stMtajiNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array("nextPlayer" => 20, "declareTakasia" => 22, "endGame" => 99)
    ),
    
    // Second phase special move kutakatia
    22 => array(
        "name" => "takasiaMoveSelection",
        "description" => clienttranslate('${actplayer} has a takasiaed pit and must make a move'),
        "descriptionmyturn" => clienttranslate('${you} have a takasiaed pit and must make a move'),
        "type" => "activeplayer",
        "args" => "argTakasiaMoveSelection",
        "possibleactions" => array("selectMove"),
        "transitions" => array("selectMove" => 21, "zombiePass" => 21)
    ),

    // Hus type. Player selects a bowl, then a direction
    30 => array(
        "name" => "husMoveSelection",
        "description" => clienttranslate('${actplayer} must make a move'),
        "descriptionmyturn" => clienttranslate('${you} must make a move'),
        "type" => "activeplayer",
        "args" => "argHusMoveSelection",
        "possibleactions" => array("selectMove"),
        "transitions" => array("selectMove" => 31, "zombiePass" => 31)
    ),

    // Hus type game checks situation if next player is on, or one player wins
    21 => array(
        "name" => "husMoveExecution",
        "description" => clienttranslate('Move of ${actplayer} gets executed'),
        "descriptionmyturn" => clienttranslate('Your move gets executed'),
        "type" => "game",
        "action" => "stHusNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array("nextPlayer" => 30, "endGame" => 99)
    ),
    
    // Final state, one player has won
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);
