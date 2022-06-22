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

$game_options = array(
    100 => array(
        'name' => totranslate('Bao variant'),
        'values' => array(
            // Full version of the game
            1 => array(
                'name' => totranslate('Bao La Kiswahili'),
                'description' => totranslate('Full version of the game with sowing phase and harvesting phase.'),
                'tmdisplay' => totranslate('Kiswahili variant')
            ),

            // Faster version starting with 2nd phase
            2 => array(
                'name' => totranslate('Bao La Kujifunza'),
                'description' => totranslate('Easier and quicker version of the game with fixed setup and only harvesting phase.'),
                'tmdisplay' => totranslate('Kujifunza variant')
            ),

            // Simple rule versions
            3 => array(
                'name' => totranslate('Hus Bao'),
                'description' => totranslate('Simplified version of the game with fixed setup, only harvesting phase and simpler rules.'),
                'tmdisplay' => totranslate('Hus variant')
            ),
        ),
        'default' => 1
    ),
    101 => array(
        'name' => totranslate('Board editor at start'),
        'values' => array(
            // Without editor
            1 => array(
                'name' => totranslate('off'),
                'description' => totranslate('Regular board setup depending on selected game variant')
            ),

            // With editor
            2 => array(
                'name' => totranslate('on'),
                'description' => totranslate('Editor for free board setup at game start for selected game variant')
            )
        ),
        'displaycondition' => array( 
            // display this option only for training mode
           array( 'type' => 'otheroption', 
                'id' => 201, // ELO OFF hardcoded framework option
                'value' => 1 // 1 if OFF
           )
        ),
        'default' => 1
    ),
    102 => array(
        'name' => totranslate('Seed selection'),
        'values' => array(
            // Shiny green stones
            1 => array(
                'name' => totranslate('Modern semiprecious stone'),
                'description' => totranslate('Play with shiny green stones as shown in the rule book'),
                'tmdisplay' => totranslate('Modern seeds')
            ),

            // Traditional mkomwe seeds
            2 => array(
                'name' => totranslate('Traditional mkomwe seed'),
                'description' => totranslate('Play with dark green traditional mkomwe seeds (caesalpinia bonduc)'),
                'tmdisplay' => totranslate('Traditional seeds')
            )
        ),
        'default' => 1
    )
);

$game_preferences = array(
    100 => array(
        'name' => totranslate('Kichwa selection mode if unambiguous'),
        'needReload' => false,
        'values' => array(
            // when a kichwa has to be selected after capture, let user click, even if unambigious
            1 => array( 
                'name' => totranslate( 'Manual kichwa selection')
            ),

            // when a kichwa has to be selected after capture, automatically select it, if unabigious
            2 => array( 
                'name' => totranslate( 'Autmatic kichwa selection')
            )
        ),
        'default' => 1
    )
);
