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
        "transitions" => array("" => 10)
    ),

    // player selects a bowl, cancels his selection or 
    // gives up to not having to play a hopeless game til the end
    10 => array(
        "name" => "bowlSelection",
        "description" => clienttranslate('${actplayer} must select a bowl'),
        "descriptionmyturn" => clienttranslate('${you} must select a bowl'),
        "type" => "activeplayer",
        "args" => "argBowlSelect",
        "possibleactions" => array("selectBowl"),
        "transitions" => array("selectBowl" => 11, "zombiePass" => 20)
    ),

    // player selects direction where stones should be moved to;
    // game moves stones from selected bowl in the selected direction
    // which is making one move; moves are continued until destination
    // field is empty
    11 => array(
        "name" => "moveDirection",
        "description" => clienttranslate('${actplayer} must select a direction'),
        "descriptionmyturn" => clienttranslate('${you} must select a direction'),
        "type" => "activeplayer",
        "args" => "argDirectionSelect",
        "possibleactions" => array("selectDirection", "cancelDirection"),
        "transitions" => array("selectDirection" => 20, "cancelDirection" => 10, "zombiePass" => 20)
    ),

    // game checks situation if next player is on or if current one wins
    20 => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array("nextPlayer" => 10, "endGame" => 99)
    ),
    
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);
