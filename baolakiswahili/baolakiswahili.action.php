<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BaoLaKiswahili implementation : © <Alexander Rühl> <alex@geziefer.de>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 */


class action_baolakiswahili extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "baolakiswahili_baolakiswahili";
            self::trace("Complete reinitialization of board game");
        }
    }

    // Action after selecting a move (start field and direction field),
    // also applies for special moves with other states
    public function executeMove()
    {
        self::setAjaxMode();
        $player = self::getArg("player", AT_posint, true);
        $field = self::getArg("field", AT_posint, true);
        $direction = self::getArg("direction", AT_posint, true);
        $result = $this->game->executeMove($player, $field, $direction);
        self::ajaxResponse();
    }

    // Action after finish editing board, game may start
    public function startWithEditedBoard()
    {
        self::setAjaxMode();
        // TODO: transfer parameters
        $this->game->startWithEditedBoard();
        self::ajaxResponse();
    }

    // Action for TESTMODE (enabled in JS), sets stones on board for test constellations
    public function testmode()
    {
        self::setAjaxMode();
        $this->game->testmode();
        self::ajaxResponse();
    }

}
