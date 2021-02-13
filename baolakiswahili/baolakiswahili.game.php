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


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');

class BaoLaKiswahili extends Table
{
    function __construct()
    {
        parent::__construct();

        self::initGameStateLabels(array(
        ));
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "baolakiswahili";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_score, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','32','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        $sql = "INSERT INTO board (player, field, stones) VALUES ";
        $values = array();
        list($player1, $player2) = array_keys($players);
        for ($i = 1; $i <= 16; $i++) {
            $values[] = "('$player1', '$i', '2')";
            $values[] = "('$player2', '$i', '2')";
        }
        $i = 1;

        $sql .= implode(',', $values);
        self::DbQuery($sql);

        // Init stats
        self::initStat('player', 'overallMoved', 0);
        self::initStat('player', 'overallStolen', 0);
        self::initStat('player', 'overallEmptied', 0);

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);

        $sql = "SELECT player player, field no, stones count FROM board ";
        $result['board'] = self::getObjectListFromDB($sql);

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // Since usually the game ends by oponent having no stones left in 1st row, we calculate current progression
        // as inverse procentual ratio of smaller and bigger first row count;
        // note: since this is not linear, but will go in both directions, the value may rise and fall
        $board = self::getBoard();
        $player = self::getActivePlayerId();
        $playerCount = self::getFirstRowCount($player, $board);
        $oponent = self::getPlayerAfter($player);
        $oponentCount = self::getFirstRowCount($oponent, $board);
        $minCount = min($playerCount, $oponentCount);
        $maxCount = max($playerCount, $oponentCount);
        $ratio = $minCount / $maxCount;
        return (1 - $ratio) * 100;
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    // Get the complete board with a double associative array player/no -> count
    function getBoard()
    {
        $sql = "SELECT player player, field no, stones count, stones countBackup FROM board ";
        return self::getDoubleKeyCollectionFromDB($sql);
    }

    // Get selected field of player from db
    function getSelectedField($player_id)
    {
        $sql = "SELECT selected_field FROM player WHERE player_id = '$player_id'";
        return self::getUniqueValueFromDB($sql);
    }

    // Possible bowls to select are all of players bowls with at least 2 stones
    function getPossibleBowls($player_id)
    {
        $result = array();

        $board = self::getBoard();

        for ($i = 1; $i <= 16; $i++) {
            if ($board[$player_id][$i]["count"] >= 2) {
                $result[$player_id][$i] = $board[$player_id][$i];
            }
        }

        return $result;
    }

    // Possible directions to select are always the previous and next bowl to the selected one
    function getPossibleDirections($player_id, $selected)
    {
        $result = array();

        $board = self::getBoard();
        $left = $selected == 1 ? 16 : $selected - 1;
        $right = $selected == 16 ? 1 : $selected + 1;

        $result[$player_id][$left] = true;
        $result[$player_id][$selected] = false;
        $result[$player_id][$right] = true;
        return $result;
    }

    // Calculate next field from given field in given direction (-1 / +1)
    function getNextField($field, $direction)
    {
        // calculate next field to move to, adapt overflow in field no
        $destinationField = $field + $direction;
        $destinationField = ($destinationField == 0) ? 16 : $destinationField;
        $destinationField = ($destinationField == 17) ? 1 : $destinationField;

        return $destinationField;
    }

    // Calculate player's stones in first row
    function getFirstRowCount($player, $board)
    {
        // first check if the player can still move and sum up stones
        $sum = 0;
        for ($i = 1; $i <= 8; $i++) {
            $count = $board[$player][$i]["count"];
            $sum += $count;
        }

        return $sum;
    }

    // Calculate player's score, which is 0 if lost or sum of fields if not;
    // board can be given or null
    function getScore($player, $board)
    {
        // first check if the player can still move and sum up stones
        $sum = 0;
        $canMove = false;
        for ($i = 1; $i <= 16; $i++) {
            $count = $board[$player][$i]["count"];
            $sum += $count;
            if ($count > 1) {
                $canMove = true;
            }
        }

        // if he can move, check if 1st line is not empty
        $isEmpty = true;
        if ($canMove) {
            for ($i = 1; $i <= 8; $i++) {
                if ($board[$player][$i]["count"] > 0) {
                    $isEmpty = false;
                    break;
                }
            }
        }

        // check both conditions for determine if lost
        if ($canMove && !$isEmpty) {
            return $sum;
        } else {
            return 0;
        }
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in baolakiswahili.action.php)
    */

    // player has selected a bowl for his move
    function selectBowl($player, $field)
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction('selectBowl');

        // Check that selection is possible
        $possibleBowls = self::getPossibleBowls($player);
        if ($possibleBowls[$player][$field] >= 2) {
            // Save selected bowl
            $sql = "UPDATE player SET selected_field = $field where player_id = $player";
            self::DbQuery($sql);

            // Then, go to the next state
            $this->gamestate->nextState('selectBowl');
        } else {
            throw new feException("Impossible move");
        }
    }

    // player has selected a direction for his move
    // and move is calculated
    function selectDirection($player, $field)
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction('selectDirection');

        // Check that selection is possible
        $selectedField = self::getSelectedField($player);
        $possibleDirection = self::getPossibleDirections($player, $selectedField);
        if ($possibleDirection[$player][$field]) {
            // we only want to have -1 or +1, thus correct if overflown
            $moveDirection = ($field - $selectedField);
            $moveDirection = (abs($moveDirection) > 1) ? $moveDirection / -15 : $moveDirection;

            // get start situation
            $oponent = self::getPlayerAfter($player);
            $players = array($player, $oponent);
            $sourceField = $selectedField;
            $board = self::getBoard();

            // initialize result array for later notification of moves to do;
            // moves are ordered list of pattern "<command>_<field>"
            // where command is: emptyActive, emptyOponent, moveStone
            $moves = array();

            // get stones for move and empty the start field
            $count = $board[$player][$sourceField]["count"];
            $board[$player][$sourceField]["count"] = 0;
            array_push($moves, "emptyActive_" . $sourceField);
            $overallMoved = $count;
            $overallStolen = 0;
            $overallEmptied = 0;

            // make moves until last field was empty before putting stone
            while ($count > 1) {
                // distribute stones in the next fields in selected direction until last one
                while ($count > 0) {
                    // calculate next field to move to and leave 1 stone
                    $destinationField = self::getNextField($sourceField, $moveDirection);
                    $board[$player][$destinationField]["count"] += 1;
                    array_push($moves, "moveStone_" . $destinationField);
                    $sourceField = $destinationField;
                    $count -= 1;
                }

                // source field now points to field of last put stone
                $count = $board[$player][$sourceField]["count"];

                if ($count > 1) {
                    // empty oponents oposite bowl in 1st row and add to own stones for move,
                    // if empty, nothing changes
                    if ($sourceField <= 8) {
                        $countOponent = $board[$oponent][$sourceField]["count"];
                        if ($countOponent > 0) {
                            // empty and count stones
                            $overallStolen += $countOponent;
                            $overallEmptied += 1;
                            $count += $countOponent;
                            $board[$oponent][$sourceField]["count"] = 0;
                            array_push($moves, "emptyOponent_" . $sourceField);
                            $overallMoved += $count;

                            // check if oponent has lost and stop moves if lost
                            $scoreOponent = self::getScore($oponent, $board);
                            if ($scoreOponent == 0) {
                                break;
                            }
                        }
                    }

                    // empty own bowl for next move
                    $board[$player][$sourceField]["count"] = 0;
                    array_push($moves, "emptyActive_" . $sourceField);
                }
            }

            // save all changed fields and update score
            foreach ($players as $player_id) {
                for ($field = 1; $field <= 16; $field++) {
                    $count = $board[$player_id][$field]["count"];
                    $countBackup = $board[$player_id][$field]["countBackup"];
                    if ($count <> $countBackup) {
                        $sql = "UPDATE board SET stones = '$count' WHERE player = '$player_id' AND field = '$field'";
                        self::DbQuery($sql);
                    }
                }
            }

            // clear selections
            $sql = "UPDATE player SET selected_field = NULL WHERE player_id = '$player'";
            self::DbQuery($sql);

            // update statistics
            self::incStat($overallMoved, "overallMoved", $player);
            self::incStat($overallStolen, "overallStolen", $player);
            self::incStat($overallEmptied, "overallEmptied", $player);

            // notify players of all moves
            $messageDirection = ($moveDirection < 0) ? clienttranslate('down') : clienttranslate('up');
            $message = clienttranslate('${player_name} moved ${messageDirection} from field ${selectedField} to field ${sourceField} emptying ${overallEmptied} bowl(s).');
            self::notifyAllPlayers("moveStones", $message, array(
                'player' => $player,
                'player_name' => self::getActivePlayerName(),
                'oponent' => $oponent,
                'messageDirection' => $messageDirection,
                'selectedField' => $selectedField,
                'sourceField' => $sourceField,
                'overallEmptied' => $overallEmptied,
                'moves' => $moves,
                'board' => $board
            ));

            // Go to the next state
            $this->gamestate->nextState('selectDirection');
        } else {
            throw new feException("Impossible move");
        }
    }

    // player has canceled the direction selection
    function cancelDirection($player, $field)
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction('selectDirection');

        // Check that selection is possible
        $selected = self::getSelectedField($player);
        if ($selected == $field) {
            // delete selection
            $sql = "UPDATE player SET selected_field = NULL where player_id = $player";
            self::DbQuery($sql);

            // Then go to the next state
            $this->gamestate->nextState('cancelDirection');
        } else {
            throw new feException("Impossible move");
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argBowlSelect()
    {
        return array(
            'possibleBowls' => self::getPossibleBowls(self::getActivePlayerId())
        );
    }

    function argDirectionSelect()
    {
        $field = self::getSelectedField(self::getActivePlayerId());

        return array(
            'possibleDirections' => self::getPossibleDirections(self::getActivePlayerId(), $field)
        );
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stNextPlayer()
    {
        // get current situation
        $board = self::getBoard();

        // calculate scores and thereby if someone has lost (score = 0)
        $playerLast = self::getActivePlayerId();
        $scoreLast = self::getScore($playerLast, $board);
        $playerNext = self::activeNextPlayer();
        $scoreNext = self::getScore($playerNext, $board);

        // save scores
        $sql = "UPDATE player SET player_score = '$scoreLast' WHERE player_id ='$playerLast'";
        self::DbQuery($sql);
        $sql = "UPDATE player SET player_score = '$scoreNext' WHERE player_id ='$playerNext'";
        self::DbQuery($sql);

        // notify players of new scores
        $newScores = array($playerLast => $scoreLast, $playerNext => $scoreNext);
        self::notifyAllPlayers("newScores", "", array(
            "scores" => $newScores
        ));

        // set next state depending on lost state
        if ($scoreLast == 0 || $scoreNext == 0) {
            $this->gamestate->nextState('endGame');
        } else {
            // Next player can play and gets extra time
            self::giveExtraTime($playerNext);
            $this->gamestate->nextState('nextPlayer');
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');

            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

    }
}
