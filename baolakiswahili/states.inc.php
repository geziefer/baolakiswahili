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

    // First phase of kiswahili type, player selects a bowl, then a direction
    10 => array(
        "name" => "kunamuaMoveSelection",
        "description" => clienttranslate('${actplayer} must place seed and make ${type} move'),
        "descriptionmyturn" => clienttranslate('${you} must place seed for ${type} move'),
        "type" => "activeplayer",
        "args" => "argKunamuaMoveSelection",
        "possibleactions" => array("executeMove"),
        "transitions" => array("executeMove" => 11, "zombiePass" => 11)
    ),

    // First phase game checks situation if next player is on, a special move oocurs, or one player wins
    11 => array(
        "name" => "kunamuaMoveExecution",
        "description" => clienttranslate('Move of ${actplayer} gets executed'),
        "descriptionmyturn" => clienttranslate('Your move gets executed'),
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array("nextPlayer" => 10, "continueCapture" => 12, "decideSafari" => 13, "selectTax" => 14, "switchPhase" => 20, "endGame" => 99)
    ),
    
    // First phase decision of making safari
    12 => array(
        "name" => "kunamuaCaptureSelection",
        "description" => clienttranslate('${actplayer} must select kichwa'),
        "descriptionmyturn" => clienttranslate('${you} must select kichwa'),
        "type" => "activeplayer",
        "args" => "argKunamuaCaptureSelection",
        "possibleactions" => array("selectKichwa"),
        "transitions" => array("selectKichwa" => 11, "zombiePass" => 11)
    ),

    // First phase decision of making safari
    13 => array(
        "name" => "safariDecision",
        "description" => clienttranslate('${actplayer} must decide about safari'),
        "descriptionmyturn" => clienttranslate('${you} must decide about safari'),
        "type" => "activeplayer",
        "args" => "argSafariDecision",
        "possibleactions" => array("continueMove"),
        "transitions" => array("continueMove" => 11, "zombiePass" => 11)
    ),

    // First phase player selects a kichwa after capture
    14 => array(
        "name" => "nyumbaTaxSelection",
        "description" => clienttranslate('${actplayer} must tax nyumba'),
        "descriptionmyturn" => clienttranslate('${you} must tax nyumba'),
        "type" => "activeplayer",
        "args" => "argNyumbaTaxSelection",
        "possibleactions" => array("taxNyumba"),
        "transitions" => array("taxNyumba" => 11, "zombiePass" => 11)
    ),

    // Second phase or kujifunza type, player selects a pit, then a direction
    20 => array(
        "name" => "mtajiMoveSelection",
        "description" => clienttranslate('${actplayer} must make ${type} move'),
        "descriptionmyturn" => clienttranslate('${you} must select pit for ${type}'),
        "type" => "activeplayer",
        "args" => "argMtajiMoveSelection",
        "possibleactions" => array("executeMove"),
        "transitions" => array("executeMove" => 21, "zombiePass" => 21)
    ),

    // Second phase game checks situation if next player is on, a special move oocurs, or one player wins
    21 => array(
        "name" => "mtajiMoveExecution",
        "description" => clienttranslate('Move of ${actplayer} gets executed'),
        "descriptionmyturn" => clienttranslate('Your move gets executed'),
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array("nextPlayer" => 20, "continueCapture" => 22, "declareKutakatia" => 23, "endGame" => 99)
    ),
    
    // Second phase or kujifunza type. Player selects a kichwa after capture
    22 => array(
        "name" => "mtajiCaptureSelection",
        "description" => clienttranslate('${actplayer} must select kichwa'),
        "descriptionmyturn" => clienttranslate('${you} must select kichwa'),
        "type" => "activeplayer",
        "args" => "argMtajiCaptureSelection",
        "possibleactions" => array("selectKichwa"),
        "transitions" => array("executeMove" => 21, "zombiePass" => 21)
    ),

    // Second phase special move kutakatia
    23 => array(
        "name" => "kutakatiaMoveSelection",
        "description" => clienttranslate('${actplayer} has takasiaed pit and must make a move'),
        "descriptionmyturn" => clienttranslate('${you} have takasiaed pit and must make a move'),
        "type" => "activeplayer",
        "args" => "argTakasiaMoveSelection",
        "possibleactions" => array("executeMove"),
        "transitions" => array("executeMove" => 21, "zombiePass" => 21)
    ),

    // Hus type, player selects a pit, then a direction
    30 => array(
        "name" => "husMoveSelection",
        "description" => clienttranslate('${actplayer} must make a move'),
        "descriptionmyturn" => clienttranslate('${you} must select a pit'),
        "type" => "activeplayer",
        "args" => "argHusMoveSelection",
        "possibleactions" => array("executeMove"),
        "transitions" => array("executeMove" => 31, "zombiePass" => 31)
    ),

    // Hus type game checks situation if next player is on, or one player wins
    31 => array(
        "name" => "husMoveExecution",
        "description" => clienttranslate('Move of ${actplayer} gets executed'),
        "descriptionmyturn" => clienttranslate('Your move gets executed'),
        "type" => "game",
        "action" => "stNextPlayer",
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
