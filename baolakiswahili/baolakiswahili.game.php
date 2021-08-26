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

/**
 * Code notes:
 * 
 * how to log variable / a message:
 * self::dump('##################### $variable', $variable);
 * self::debug('##################### message');
 */



require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');

// Local constants
define( "OPTION_VARIANT", 100 );
define( "VARIANT_KISWAHILI", 1 );
define( "VARIANT_KUJIFUNZA", 2 );
define( "VARIANT_HUS", 3 );

class BaoLaKiswahili extends Table
{
    function __construct()
    {
        self::initGameStateLabels(array(
            "game_variant" => 100
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
            $values[] = "('" . $player_id . "','$color','0','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        $sql = "INSERT INTO board (player, field, stones) VALUES ";
        $values = array();
        list($player1, $player2) = array_keys($players);

        // in Kiswahili variant only 3 bowls per player are filled
        if ($options[OPTION_VARIANT] == VARIANT_KISWAHILI) {
            for ($i = 1; $i <= 4; $i++) {
                $values[] = "('$player1', '$i', '0')";
            }
            $values[] = "('$player1', '5', '6')";
            $values[] = "('$player1', '6', '2')";
            $values[] = "('$player1', '7', '2')";
            for ($i = 8; $i <= 16; $i++) {
                $values[] = "('$player1', '$i', '0')";
            }

            $values[] = "('$player2', '1', '0')";
            $values[] = "('$player2', '2', '2')";
            $values[] = "('$player2', '3', '2')";
            $values[] = "('$player2', '4', '6')";
            for ($i = 5; $i <= 16; $i++) {
                $values[] = "('$player2', '$i', '0')";
            }

            // all other stones are placed in front of board (represented by field 0)
            $values[] = "('$player1', '0', '22')";
            $values[] = "('$player2', '0', '22')";
        }
        else {
            // in other variants all bowls contain 2 stones
            for ($i = 1; $i <= 16; $i++) {
                $values[] = "('$player1', '$i', '2')";
                $values[] = "('$player2', '$i', '2')";
            }

            // no stones in storage area
            $values[] = "('$player1', '0', '0')";
            $values[] = "('$player2', '0', '0')";
        }

        $sql .= implode(',', $values);
        self::DbQuery($sql);

        // Init scores
        $board = $this->getBoard();
        $score = $this->getScore($player1, $board);
        $sql = "UPDATE player SET player_score = $score where player_id = $player1";
        self::DbQuery($sql);
        $score = $this->getScore($player2, $board);
        $sql = "UPDATE player SET player_score = $score where player_id = $player2";
        self::DbQuery($sql);

        // Init stats
        self::initStat('player', 'overallMoved', 0);
        self::initStat('player', 'overallStolen', 0);
        self::initStat('player', 'overallEmptied', 0);

        // Init key value store
        $sql = "INSERT INTO kvstore(`key`, value_text, value_number) VALUES ('stateAfterMove', 'nextPlayer', null)";
        self::DbQuery($sql);
        $sql = "INSERT INTO kvstore(`key`, value_text, value_number) VALUES ('captureField', null, 0)";
        self::DbQuery($sql);
        $sql = "INSERT INTO kvstore(`key`, value_text, value_number) VALUES ('moveDirection', null, 0)";
        self::DbQuery($sql);

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

        $result['variant'] = $this->getGameStateValue('game_variant');

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
        $board = $this->getBoard();
        $player = self::getActivePlayerId();
        $playerCount = $this->getFirstRowCount($player, $board);
        $oponent = self::getPlayerAfter($player);
        $oponentCount = $this->getFirstRowCount($oponent, $board);
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

    // Gets the bowl number of the player's nyumba
    function getNyumba($player_id)
    {
        // due to the way, the board is persisted in database, the position of the nyumba is 
        // either 6 (for 1st player) or 4 (for 2nd player)
        $sql = "SELECT player_no FROM player WHERE player_id = '$player_id'";
        $playerNo = self::getUniqueValueFromDB($sql);

        return $playerNo == 1 ? 5 : 4;
    }

    // Hus variant: Possible bowls and their direction of a player's bowls with at least 2 stones
    function getHusPossibleMoves($player_id)
    {
        $result = array();

        $board = $this->getBoard();

        for ($i = 1; $i <= 16; $i++) {
            if ($board[$player_id][$i]["count"] >= 2) {
                $left = $i == 1 ? 16 : $i - 1;
                $right = $i == 16 ? 1 : $i + 1;
                $result[$i] = array($left, $right);
            }
        }

        return $result;
    }

    // mtaji phase step #1: if possible, a capture move has to be played
    function getMtajiPossibleCaptures($player_id)
    {
        $result = array();

        $board = $this->getBoard();
        $oponent = self::getPlayerAfter($player_id);

        // check if any bowl with at least 2 and at most 15 stones leads to a harvest in initial move
        for ($i = 1; $i<= 16; $i++) {
            $count = $board[$player_id][$i]["count"];
            if ($count >=2 && $count <= 15) {
                // will take left and/or right move if possible
                $subResult = array();

                // check if move to the left gets to 1st row, lands in non-empty bowl and has an adjacent filled bowl
                $destinationField = $this->getDestinationField($i, -1, $count);
                if ($destinationField <= 8 && $board[$player_id][$destinationField]["count"] > 0 &&
                    $board[$oponent][$destinationField]["count"] > 0) {
                    $left = $i == 1 ? 16 : $i - 1;
                    array_push($subResult, $left);
                }

                // check if move to the right gets to 1st, lands in non-empty bowl row and has an adjacent filled bowl
                $destinationField = $this->getDestinationField($i, 1, $count);
                if ($destinationField <= 8 && $board[$player_id][$destinationField]["count"] > 0 &&
                    $board[$oponent][$destinationField]["count"] > 0) {
                    $right = $i == 16 ? 1 : $i + 1;
                    array_push($subResult, $right);
                }

                // only add to result if a harvest move was found
                if (!empty($subResult)) {
                    $result[$i] = $subResult;
                }
            }
        }

        return $result;
    }

    // mtaji phase step #2: if no harvest move was found, check for non-harvest moves,
    // assumes that before the caputure step was checked
    function getMtajiPossibleNonCaptures($player_id)
    {
        $result = array();

        $board = $this->getBoard();

        // first check if any bowl in the 1st row has at least 2 stones, then in the 2nd row
        for ($i = 1; $i <= 8; $i++) {
            if ($board[$player_id][$i]["count"] >= 2) {
                $left = $i == 1 ? 16 : $i - 1;
                $right = $i + 1;
                $result[$i] = array($left, $right);
            }
        }
        if (empty($result)) {
            for ($i = 9; $i <= 16; $i++) {
                if ($board[$player_id][$i]["count"] >= 2) {
                    $left = $i - 1;
                    $right =  $i == 16 ? 1 : $i + 1;
                    $result[$i] = array($left, $right);
                }
            }
        }

        return $result;
    }

    // move from kichwa after capture in Kiswahili or Kujifunza variant
    function getPossibleKichwas($player_id)
    {
        $result = array();

        $oponent = $this->getPlayerAfter($player_id);

        // get stored capture field and move direction from last move execution
        $sql = "SELECT value_number FROM kvstore WHERE `key` = 'captureField'";
        $captureField = $this->getUniqueValueFromDB($sql);
        $sql = "SELECT value_number FROM kvstore WHERE `key` = 'moveDirection'";
        $moveDirection = $this->getUniqueValueFromDB($sql);

        // be sure that it is a valid bowl from front row and a valid direction
        if($captureField < 1 || $captureField > 8 || abs($moveDirection) > 1) {
            throw new feException("Impossible move: capture field should be between 1 and 8 and direction between -1 and 1 when selecting possible kichwa");  
        }

        // left kichwa has to be chosen if capture happens in left kichwa or kimbi
        // or direction from previous move was already clockwise without capture in right kichwa or kimbi
        if ($captureField <= 2 || ($moveDirection == 1 && $captureField < 7)) {
            $result[1] = array(2, $oponent.'_'.$captureField);
        } 
        // right kichwa has to be chosen if capture happens in right kichwa or kimbi
        // or direction from previous move was already counterclockwise without capture in left kichwa or kimbi
        elseif ($captureField >= 7 || ($moveDirection == -1 && $captureField > 2)) {
            $result[8] = array(7, $oponent.'_'.$captureField);
        }
        // both kichwas may be chosen if capture happens in middle and direction is not yet set
        elseif ($moveDirection == 0 && $captureField > 2 && $captureField < 7) {
            $result[1] = array(2, $oponent.'_'.$captureField);
            $result[8] = array(7, $oponent.'_'.$captureField);
        }
        // invalid combination
        else {
            throw new feException("Impossible move: invalid combination for capture field and move direction when selecting possible kichwa");  
        }

        return $result;
    }
  
    // kunamua phase step #1: if possible, a capture move has to be played
    function getKunamuaPossibleCaptures($player_id)
    {
        $result = array();

        $board = $this->getBoard();
        $oponent = self::getPlayerAfter($player_id);

        // check if any non-empty bowl in the 1st row has oposite stones
        for ($i = 1; $i<= 8; $i++) {
            $countPlayer = $board[$player_id][$i]["count"];
            $countOponent = $board[$oponent][$i]["count"];

            //  add to result if a harvest move was found, direction is not yet set
            if ($countPlayer >= 1 && $countOponent >= 1) {
                $result[$i] = array(0);
            }
        }

        return $result;
    }

    // kunamua phase step #2: if no harvest move was found, check for non-harvest moves,
    // assumes that before the caputure step was checked
    function getKunamuaPossibleNonCaptures($player_id)
    {
        $result = array();

        $board = $this->getBoard();
        $nyumba = $this->getNyumba($player_id);
        // check all bowl in the 1st row for stones
        for ($i = 1; $i <= 8; $i++) {
            // skip nyumba for now
            if ($i != $nyumba && $board[$player_id][$i]["count"] >= 1) {
                $left = $i == 1 ? 16 : $i - 1;
                $right = $i + 1;
                $result[$i] = array($left, $right);
            }
        }
        // only add nyumba now if no other was found
        if (empty($result) && $board[$player_id][$nyumba]["count"] >= 1) {
            $left = $nyumba - 1;
            $right = $nyumba + 1;
            $result[$nyumba] = array($left, $right);
        }

        return $result;
    }

    // Determine destination field moving from given field in given direction (-1 / +1) a certain amount of fields
    function getDestinationField($field, $direction, $count)
    {
        $destinationField = $field;
        for ($i = 1; $i <= $count; $i++) {
            $destinationField = $this->getNextField($destinationField, $direction);
        }

        return $destinationField;
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

    // Calculate player's score, which is 0 if lost or sum of fields if not
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

    // player has selected a move (start field and direction field)
    // game modes are distinguished here to exeute it
    function executeMove($player, $field, $direction)
    {
        self::trace('*** executeMove was called by client with parameters player='.$player.', field='.$field.', direction='.$direction.'.');
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Action checks and move checks for all variants
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Check that this player is active and that this action is possible at this moment,
        // store action name for further distinguishing
        if ($this->checkAction('executeMove', false)) {
            $currentAction = 'executeMove';
        } elseif ($this->checkAction('selectKichwa', false)) {
            $currentAction = 'selectKichwa';
        } else {
            throw new feException("Impossible move: action is not executeMove or selectKichwa");
        }

        // Distinguish game mode for move check
        if ($this->getGameStateValue('game_variant') == VARIANT_KISWAHILI) {
            // Check that move is possible, distinguish action
            if ($currentAction == 'executeMove') {
                $possibleCaptures = $this->getKunamuaPossibleCaptures($player);
                $possibleNonCaptures = array();
                if (empty($possibleCaptures)) {
                    $possibleNonCaptures = $this->getKunamuaPossibleNonCaptures($player);
                }
                if ((!array_key_exists($field, $possibleCaptures) || array_search($direction, $possibleCaptures[$field]) === false) &&
                    (!array_key_exists($field, $possibleNonCaptures) || array_search($direction, $possibleNonCaptures[$field]) === false)) {
                    throw new feException("Impossible move: move to execute is not in possible caputures or possible non-captures");
                }
            } elseif ($currentAction == 'selectKichwa') {
                $possibleKichwas = $this->getPossibleKichwas($player);
                if (!array_key_exists($field, $possibleKichwas) || array_search($direction, $possibleKichwas[$field]) === false) {
                    throw new feException("Impossible move: move to execute is not in possible kichwas");
                }
            }
        } elseif ($this->getGameStateValue('game_variant') == VARIANT_KUJIFUNZA) {
            // Check that move is possible, distinguish action
            if ($currentAction == 'executeMove') {
                $possibleCaptures = $this->getMtajiPossibleCaptures($player);
                $possibleNonCaptures = array();
                if (empty($possibleCaptures)) {
                    $possibleNonCaptures = $this->getMtajiPossibleNonCaptures($player);
                }
                if ((!array_key_exists($field, $possibleCaptures) || array_search($direction, $possibleCaptures[$field]) === false) &&
                    (!array_key_exists($field, $possibleNonCaptures) || array_search($direction, $possibleNonCaptures[$field]) === false)) {
                    throw new feException("Impossible move: move to execute is not in possible caputures or possible non-captures");
                }
            } elseif ($currentAction == 'selectKichwa') {
                $possibleKichwas = $this->getPossibleKichwas($player);
                if (!array_key_exists($field, $possibleKichwas) || array_search($direction, $possibleKichwas[$field]) === false) {
                    throw new feException("Impossible move: move to execute is not in possible kichwas");
                }
            }
        } elseif ($this->getGameStateValue('game_variant') == VARIANT_HUS) {
            // Check that move is possible
            $possibleMoves = $this->getHusPossibleMoves($player);
            if (!array_key_exists($field, $possibleMoves) || array_search($direction, $possibleMoves[$field]) === false) {
                throw new feException("Impossible move: move to execute is not in possible moves");
            }
        } else {
            // error in options
            throw new feException("Impossible move: games option does not exist");
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Common preparations for all variants
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Common move execution preparation
        // we only want to have -1 or +1, thus correct if overflown, exclude unset direction
        if ($direction == 0) {
            $moveDirection = 0;
        } else {
            $moveDirection = $direction - $field;
            $moveDirection = (abs($moveDirection) > 1) ? $moveDirection / -15 : $moveDirection;
        }

        // get start situation
        $oponent = $this->getPlayerAfter($player);
        $players = array($player, $oponent);
        $selectedField = $field;
        $sourceField = $field;
        $board = $this->getBoard();

        // some game situation, such as capturing, are clear after move execution, but state will be set in stNextPlayer,
        // so set nextPlayer as default state after move, which might be overwritten by special state (e.g. continueCapture)
        $stateAfterMove = 'nextPlayer';
        // also the field from which a capture occurs has to be stored for using in later steps to allow for correct selection,
        // default value is 0 (no caputure), can be overwritten with 1 - 8
        $captureField = 0;

        // initialize result array for later notification of moves to do;
        // moves are ordered list of pattern "<command>_<field>"
        // where possible commands are: 
        // emptyActive: empty own field
        // emptyOponent: empty oponent's field
        // moveActive: move own stones to field
        // move Oponent: move oponent's captured stones to field
        // placeActive: place a new stone from storage into field
        $moves = array();

        // for statistics - total number of stones moved from player
        $overallMoved = 0;
        // for statistics - total number of stolen stones from oponent
        $overallStolen = 0;
        // for statistics - total number of bowls emtied from oponent
        $overallEmptied = 0;

        // Distinguish game mode for move execution
        if ($this->getGameStateValue('game_variant') == VARIANT_KISWAHILI && $currentAction == 'executeMove') {
            // take bowl out of storage area and put in bowl 
            $board[$player][0]["count"] -= 1;
            $board[$player][$sourceField]["count"] += 1;
            array_push($moves, "placeActive_" . $sourceField);
            $overallMoved += 1;

            // get stones for move
            $count = $board[$player][$sourceField]["count"];

            // distinguish capture and non-capture move
            if (!empty($possibleCaptures)) {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kiswahili variant - capture move
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // this is a capture move, further captures are allowed and captured stones require player action,
                // do nothing more than to prepare for next part of move in different state, since player has to select kichwa
                $stateAfterMove = 'continueCapture';
                $captureField = $sourceField;
            } else {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kiswahili variant - non-capture move
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // this is a non-capture move, no further captures are allowed, move only continues in own two rows,
                // first empty start field, then make moves until last field was empty before putting stone
                $board[$player][$sourceField]["count"] = 0;
                array_push($moves, "emptyActive_" . $sourceField);
                $overallMoved += $count;
                while ($count > 1) {
                    // distribute stones in the next fields in selected direction until last one
                    while ($count > 0) {
                        // calculate next field to move to and leave 1 stone
                        $destinationField = $this->getNextField($sourceField, $moveDirection);
                        $board[$player][$destinationField]["count"] += 1;
                        array_push($moves, "moveActive_" . $destinationField);
                        $sourceField = $destinationField;
                        $count -= 1;
                    }

                    // source field now points to field of last put stone
                    $count = $board[$player][$sourceField]["count"];

                    // empty own bowl for next move if it ends in non-empty bowl
                    if ($count > 1) {
                        $board[$player][$sourceField]["count"] = 0;
                        array_push($moves, "emptyActive_" . $sourceField);
                        $overallMoved += $count;
                    }
                }
            }
        } elseif ($this->getGameStateValue('game_variant') == VARIANT_KUJIFUNZA && $currentAction == 'executeMove') {
            // get stones for move
            $count = $board[$player][$sourceField]["count"];

            // empty start field
            $board[$player][$sourceField]["count"] = 0;
            array_push($moves, "emptyActive_" . $sourceField);
            $overallMoved += $count;

            // distinguish capture and non-capture move
            if (!empty($possibleCaptures)) {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kujifunza variant - capture move
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // this is a capture move, further captures are allowed and captured stones require player action,
                // distribute stones in the next fields in selected direction until last one which has to be a capture
                while ($count > 0) {
                    // calculate next field to move to and leave 1 stone
                    $destinationField = $this->getNextField($sourceField, $moveDirection);
                    $board[$player][$destinationField]["count"] += 1;
                    array_push($moves, "moveActive_" . $destinationField);
                    $sourceField = $destinationField;
                    $count -= 1;
                }
                // prepare for next part of move in different state, since player has to select kichwa
                $stateAfterMove = 'continueCapture';
                $captureField = $destinationField;
            } else {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kujifunza variant - non-capture move
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // this is a non-capture move, no further captures are allowed, move only continues in own two rows,
                // make moves until last field was empty before putting stone
                while ($count > 1) {
                    // distribute stones in the next fields in selected direction until last one
                    while ($count > 0) {
                        // calculate next field to move to and leave 1 stone
                        $destinationField = $this->getNextField($sourceField, $moveDirection);
                        $board[$player][$destinationField]["count"] += 1;
                        array_push($moves, "moveActive_" . $destinationField);
                        $sourceField = $destinationField;
                        $count -= 1;
                    }

                    // source field now points to field of last put stone
                    $count = $board[$player][$sourceField]["count"];

                    // empty own bowl for next move if it ends in non-empty bowl
                    if ($count > 1) {
                        $board[$player][$sourceField]["count"] = 0;
                        array_push($moves, "emptyActive_" . $sourceField);
                        $overallMoved += $count;
                    }
                }
            }
        } elseif (($this->getGameStateValue('game_variant') == VARIANT_KISWAHILI || $this->getGameStateValue('game_variant') == VARIANT_KUJIFUNZA) 
            && $currentAction == 'selectKichwa') {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kiwvahili or Kujifunza variant - kichwa selection
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // get stones for move
            $count = $board[$player][$sourceField]["count"];

            // kichwa after capture is selected, first get capture field from previous move and its content
            $sql = "SELECT value_number FROM kvstore WHERE `key` = 'captureField'";
            $captureField = $this->getUniqueValueFromDB($sql);
            $captureCount = $board[$oponent][$captureField]["count"];

            // start with emptying oponent's bowl
            array_push($moves, "emptyOponent_" . $captureField);
            $overallMoved += $count;
            $overallStolen += $count;
            $overallEmptied += 1;
            $board[$oponent][$captureField]["count"] = 0;

            // the move takes captured stones and starts with kichwa,
            // as every move always goes to next field, we go back one field (inverted direction) 
            // for start of move (sourceField) in order to have same behaviour later as for regular moves
            $count = $captureCount;
            $sourceField = $this->getNextField($sourceField, $moveDirection * (-1));
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kiwvahili or Kujifunza variant - move after kichwa selection
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // move on until next capture or empty bowl,
            // condition is checked at the end, since here it is possible to move only 1 stone from capture
            do {
                // distribute stones in the next fields in selected direction until last one
                while ($count > 0) {
                    // calculate next field to move to and leave 1 stone
                    $destinationField = $this->getNextField($sourceField, $moveDirection);
                    $board[$player][$destinationField]["count"] += 1;
                    array_push($moves, "moveActive_" . $destinationField);
                    $sourceField = $destinationField;
                    $count -= 1;
                }

                // check if oponent has lost and stop moves if lost
                $scoreOponent = $this->getScore($oponent, $board);
                if ($scoreOponent == 0) {
                    break;
                }
                
                // source field now points to field of last put stone
                $count = $board[$player][$sourceField]["count"];

                // if move ends in a non-empty bowl, move continues
                if ($count > 1) {
                    // check if move has another capture
                    if ($sourceField <= 8 && $board[$oponent][$sourceField]["count"] > 0) {
                        // prepare for next part of move in different state, since player has to select kichwa
                        $stateAfterMove = 'continueCapture';
                        $captureField = $sourceField;
                        // clear count to leave loop
                        $count = 0;
                    } else {
                        // empty own bowl for next move
                        $board[$player][$sourceField]["count"] = 0;
                        array_push($moves, "emptyActive_" . $sourceField);
                        $overallMoved += $count;
                    }
                }
            } while ($count > 1);
        } elseif ($this->getGameStateValue('game_variant') == VARIANT_HUS) {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Hus variant - both capture and non-capture move
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // get stones for move
            $count = $board[$player][$sourceField]["count"];

            // empty start field
            $board[$player][$sourceField]["count"] = 0;
            array_push($moves, "emptyActive_" . $sourceField);
            $overallMoved += $count;
    
            // make moves until last field was empty before putting stone
            while ($count > 1) {
                // distribute stones in the next fields in selected direction until last one
                while ($count > 0) {
                    // calculate next field to move to and leave 1 stone
                    $destinationField = $this->getNextField($sourceField, $moveDirection);
                    $board[$player][$destinationField]["count"] += 1;
                    array_push($moves, "moveActive_" . $destinationField);
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
                            $overallStolen += $count;
                            array_push($moves, "moveOponent_" . $sourceField);

                            // check if oponent has lost and stop moves if lost
                            $scoreOponent = $this->getScore($oponent, $board);
                            if ($scoreOponent == 0) {
                                break;
                            }
                        }
                    }

                    // empty own bowl for next move
                    $board[$player][$sourceField]["count"] = 0;
                    array_push($moves, "emptyActive_" . $sourceField);
                    $overallMoved += $count;
                }
            }
        }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Common finish for all variants
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Common move finish
        // save all changed fields and update score
        foreach ($players as $player_id) {
            for ($field = 0; $field <= 16; $field++) {
                $count = $board[$player_id][$field]["count"];
                $countBackup = $board[$player_id][$field]["countBackup"];
                if ($count <> $countBackup) {
                    $sql = "UPDATE board SET stones = '$count' WHERE player = '$player_id' AND field = '$field'";
                    self::DbQuery($sql);
                }
            }
        }

        // update statistics
        self::incStat(1, "turnsNumber", $player);
        self::incStat($overallMoved, "overallMoved", $player);
        self::incStat($overallEmptied, "overallEmptied", $player);
        self::incStat($overallStolen, "overallStolen", $player);

        // notify players of all moves
        if ($moveDirection < 0) {
            $messageDirection = 'clockwise';
        } elseif ($moveDirection > 0) {
            $messageDirection = 'counterclockwise';
        } else {
            $messageDirection = 'without direction';
        }
        $message = clienttranslate('${player_name} moved ${message_direction_translated} from pit ${selected_field} to pit ${source_field} in total ${overall_moved} seed(s), emptying ${overall_emptied} pit(s) and having stolen ${overall_stolen} seed(s).');
        self::notifyAllPlayers("moveStones", $message, array(
            'i18n' => array('message_direction_translated'),
            'player' => $player,
            'player_name' => self::getActivePlayerName(),
            'oponent' => $oponent,
            'message_direction_translated' => $messageDirection,
            'selected_field' => $selectedField,
            'source_field' => $sourceField,
            'overall_moved' => $overallMoved,
            'overall_emptied' => $overallEmptied,
            'overall_stolen' => $overallStolen,
            'moves' => $moves,
            'board' => $board
        ));

        // persist planned state after move and possible caputre field in database for using in stNextPlayer
        $sql = "UPDATE kvstore SET value_text = '$stateAfterMove' WHERE `key` = 'stateAfterMove'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_number = $captureField WHERE `key` = 'captureField'";
        self::DbQuery($sql);
        // persist last move direction to keep it in next move if possible
        $sql = "UPDATE kvstore SET value_number = $moveDirection WHERE `key` = 'moveDirection'";
        self::DbQuery($sql);

        // Go to the next state depending on 
        $this->gamestate->nextState('executeMove');
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argKunamuaMoveSelection()
    {
        // assume capture move
        $capture = true;
        $result = $this->getKunamuaPossibleCaptures(self::getActivePlayerId());

        // if not possible do non-capture move
        if (empty($result)) {
            $capture = false;
            $result = $this->getKunamuaPossibleNonCaptures(self::getActivePlayerId());
        }

        return array(
            'possibleMoves' => $result,
            'type' => $capture ? "capture" : "non-capture"
        );
    }

    function argSafariDecision()
    {

    }

    function argNyumbaTaxSelection()
    {

    }

    function argMtajiMoveSelection()
    {
        // assume capture move
        $capture = true;
        $result = $this->getMtajiPossibleCaptures(self::getActivePlayerId());

        // if not possible do non-capture move
        if (empty($result)) {
            $capture = false;
            $result = $this->getMtajiPossibleNonCaptures(self::getActivePlayerId());
        }

        return array(
            'possibleMoves' => $result,
            'type' => $capture ? "capture" : "non-capture"
        );
    }

    function argCaptureSelection()
    {
        return array(
            'possibleMoves' => $this->getPossibleKichwas(self::getActivePlayerId()),
            'type' => "kichwa"
        );
    }

    function argTakasiaMoveSelection()
    {

    }

    function argHusMoveSelection()
    {
        return array(
            'possibleMoves' => $this->getHusPossibleMoves(self::getActivePlayerId())
        );
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stVariantSelect()
    {
        // transit to the correct phase, which is separate for the 3 possible game options
        if ($this->getGameStateValue('game_variant') == VARIANT_KISWAHILI) {
            $this->gamestate->nextState('playKiswahili');
        } elseif ($this->getGameStateValue('game_variant') == VARIANT_KUJIFUNZA) {
            $this->gamestate->nextState('playKujifunza');
        } elseif ($this->getGameStateValue('game_variant') == VARIANT_HUS) {
            $this->gamestate->nextState('playHus');
        } else {
            // error in options, end game
            $this->gamestate->nextState('endGame');
        }
    }

    function stNextPlayer()
    {
        // get current situation
        $board = $this->getBoard();

        // calculate scores and thereby if someone has lost or is zombie (score = 0)
        $playerLast = self::getActivePlayerId();
        $sql = "SELECT player_zombie FROM player WHERE player_id = '$playerLast'";
        $zombie = self::getUniqueValueFromDB($sql);
        $scoreLast = 0;
        if (!$zombie) {
            $scoreLast = $this->getScore($playerLast, $board);
        }
        $playerNext = self::getPlayerAfter($playerLast);
        $sql = "SELECT player_zombie FROM player WHERE player_id = '$playerNext'";
        $zombie = self::getUniqueValueFromDB($sql);
        $scoreNext = 0;
        if (!$zombie) {
            $scoreNext = $this->getScore($playerNext, $board);
        }

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
            // check for saved state to use
            $sql = "SELECT value_text FROM kvstore WHERE `key` = 'stateAfterMove'";
            $stateAfterMove = self::getUniqueValueFromDB($sql);

            if ($stateAfterMove === "nextPlayer") {
                // Next player can play and gets extra time
                self::giveExtraTime($playerNext);
                $this->activeNextPlayer();
            }
            $this->gamestate->nextState($stateAfterMove);
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

        if ( $from_version <= '2103132223') {
            // move was split in 2 states and selected field was selected, does not longer exist
            $sql = "ALTER TABLE `player` DROP COLUMN `selected_field`";
            self::applyDbUpgradeToAllDB( $sql );

            // new table for storing arbitrary key value pairs
            $sql = "CREATE TABLE IF NOT EXISTS `kvstore` (`key` VARCHAR(20) NOT NULL, `value_text` VARCHAR(100), `value_number` INT, PRIMARY KEY (`key`)) ENGINE = InnoDB";
            self::applyDbUpgradeToAllDB( $sql );
        }
    }


    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// Test code for development
    //////////

    // TESTMODE only (set by JS): place stones for test purposes and changes database
    function testmode()
    {
        $player = self::getActivePlayerId();
        $oponent = self::getPlayerAfter($player);

        // board layout from perspective of start player
        // (for 2nd player field is turned 180°, so that 1st field is on the right)
        // 16 15 14 13 12 11 10 09 (oponent's 2nd row)
        // 01 02 03 04 05 06 07 08 (oponent's 1st row)
        // -----------------------
        // 01 02 03 04 05 06 07 08 (player's 1st row)
        // 16 15 14 13 12 11 10 09 (player's 2nd row)

        // save test stones for oponent
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 16");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 15");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 14");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 13");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 12");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 11");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 10");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 9");

        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 1");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 2");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 3");
        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$oponent' AND field = 4");
        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$oponent' AND field = 5");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 6");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$oponent' AND field = 7");
        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$oponent' AND field = 8");

        // save test stones for active player
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 1");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 2");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 3");
        self::DbQuery("UPDATE board SET stones = 1 WHERE player = '$player' AND field = 4");
        self::DbQuery("UPDATE board SET stones = 1 WHERE player = '$player' AND field = 5");
        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$player' AND field = 6");
        self::DbQuery("UPDATE board SET stones = 1 WHERE player = '$player' AND field = 7");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 8");

        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 16");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 15");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 14");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 13");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 12");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 11");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 10");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player' AND field = 9");
    }
}
