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

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "turns_number" => array("id"=> 10,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),
    ),
    
    // Statistics existing for each player
    "player" => array(

        "turns_number" => array("id"=> 10,
                    "name" => totranslate("Number of turns"),
                    "type" => "int" ),

        "overallMoved" => array("id"=> 11,
                    "name" => totranslate("Number of moved stones"),
                    "type" => "int" ),

        "overallStolen" => array("id"=> 12,
                    "name" => totranslate("Number of stolen stones from oponent"),
                    "type" => "int" ),

        "overallEmptied" => array("id"=> 13,
                    "name" => totranslate("Number of emptied bowls from oponent"),
                    "type" => "int" ),
    
    )

);
