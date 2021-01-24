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

    /*
    Action after selecting a bowl for move
    */
    public function selectBowl()
    {
        self::setAjaxMode();
        $player = self::getArg("player", AT_posint, true);
        $field = self::getArg("field", AT_posint, true);
        $result = $this->game->selectBowl($player, $field);
        self::ajaxResponse();
    }

    /*
    Action after selecting a direction for move
    */
    public function selectDirection()
    {
        self::setAjaxMode();
        $player = self::getArg("player", AT_posint, true);
        $field = self::getArg("field", AT_posint, true);
        $result = $this->game->selectDirection($player, $field);
        self::ajaxResponse();
    }

    /*
    Action after canceling bowl selection
    */
    public function cancelDirection()
    {
        self::setAjaxMode();
        $player = self::getArg("player", AT_posint, true);
        $field = self::getArg("field", AT_posint, true);
        $result = $this->game->cancelDirection($player, $field);
        self::ajaxResponse();
    }
}
