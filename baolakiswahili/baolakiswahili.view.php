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

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_baolakiswahili_baolakiswahili extends game_view
{
  function getGameName()
  {
    return "baolakiswahili";
  }

  function build_page($viewArgs)
  {
    // Get players & players number
    $players = $this->game->loadPlayersBasicInfos();
    $players_nbr = count($players);

    /*********** Place your code below:  ************/

    // next actions will fill this block from template
    $this->page->begin_block("baolakiswahili_baolakiswahili", "circle");

    // get 2 players and their order
    list($player1, $player2) = array_keys($players);

    // check if current player is first (2nd will get mirrored board)
    global $g_user;
    $isFirst = (array_keys($players)[0] == $g_user->get_id());

    // create 4 rows with 8 fields, scaling and shifting them according to the board perspective
    if ($isFirst) {
      $this->createLine(1, 100, 100, 85, 22, -3, -1, $player2, 16, -1);
      $this->createLine(2, 104, 100, 70, 22, 0, -5, $player2, 1, +1);
      $this->createLine(3, 107, 98, 58, 49, -1, -8, $player1, 1, +1);
      $this->createLine(4, 111, 98, 45, 66, -5, -10, $player1, 16, -1);
    } else {
      $this->createLine(1, 100, 100, 85, 22, -3, -1, $player1, 9, +1);
      $this->createLine(2, 104, 100, 70, 22, 0, -5, $player1, 8, -1);
      $this->createLine(3, 107, 98, 58, 49, -1, -8, $player2, 8, -1);
      $this->createLine(4, 111, 98, 45, 66, -5, -10, $player2, 9, +1);
    }

    /*********** Do not change anything below this line  ************/
  }

  /*
    Put circle in each field of a line and applies shifts and scale.
    */
  function createLine($y, $hor_scale, $ver_scale, $hor_shift, $ver_shift, $hor_margin, $ver_margin, $player, $startField, $increment)
  {
    $field = $startField;
    for ($x = 1; $x <= 8; $x++) {
      // insert an invisible circle for later clicking with ID according to board in DB
      $this->page->insert_block("circle", array(
        'PLAYER' => $player,
        'FIELD' => $field,
        'LEFT' => round(($x - 1) * $hor_scale + $hor_shift),
        'TOP' => round(($y - 1) * $ver_scale + $ver_shift),
        'MLEFT' => $hor_margin,
        'MTOP' => $ver_margin
      ));

      $field += $increment;
    }
  }
}
