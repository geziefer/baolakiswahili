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
                'description' => totranslate('Full version of the game with sowing phase and harvesting phase.')
            ),

            // Faster version starting with 2nd phase
            2 => array(
                'name' => totranslate('Bao La Kujifunza'),
                'description' => totranslate('Easier and quicker version of the game with fixed setup and only harvesting phase.')
            ),

            // Simple rule versions
            3 => array(
                'name' => totranslate('Hus Bao'),
                'description' => totranslate('Simplified version of the game with fixed setup, only harvesting phase and simpler rules.')
            ),
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
