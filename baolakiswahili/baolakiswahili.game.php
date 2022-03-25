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
 * self::dump('############################### $variable', $variable);
 * self::debug('############################### message');
 */

// board layout from perspective of start player (South)
// (for 2nd player field is turned 180°, so that 1st field is on the right)
// 16 15 14 13 12 11 10 09 (opponent's 2nd row)
// 01 02 03 04 05 06 07 08 (opponent's 1st row)
// -----------------------
// 01 02 03 04 05 06 07 08 (player's 1st row)
// 16 15 14 13 12 11 10 09 (player's 2nd row)


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');

// Local constants for selected game variant at game start
define( "OPTION_VARIANT", 100 );
define( "VARIANT_KISWAHILI", 1 );
define( "VARIANT_KUJIFUNZA", 2 );
define( "VARIANT_HUS", 3 );
// have an additional constant for 2nd phase of Kiswahili variant
define( "VARIANT_KISWAHILI_2ND", 4 );
// Local constants for editor mode
define( "OPTION_EDITOR", 101 );
define( "EDITOR_OFF", 1 );
define( "EDITOR_ON", 2 );
// Local constants for seed selection
define( "OPTION_SEEDS", 101 );
define( "SEEDS_MODERN", 1 );
define( "SEEDS_TRADITIONAL", 2 );

class BaoLaKiswahili extends Table
{
    function __construct()
    {
        self::initGameStateLabels(array(
            "game_variant" => 100,
            "editor" => 101,
            "seed_selection" => 102
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

        // note: BGA determines start player, player1 is South (see board layout above)
        $sql = "INSERT INTO board (player, field, stones) VALUES ";
        $values = array();
        list($player1, $player2) = array_keys($players);

        // in editor mode, board is empty
        $editDone = true;
        if ($options[OPTION_EDITOR] == EDITOR_ON) {
            for ($i = 0; $i <= 16; $i++) {
                $values[] = "('$player1', '$i', '0')";
                $values[] = "('$player2', '$i', '0')";
            }

            // marker for editor, will be toggled when done
            $editDone = false;
        }
        // in Kiswahili variant only 3 bowls per player are filled
        else if ($options[OPTION_VARIANT] == VARIANT_KISWAHILI) {
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
        // phase is used to distinguish the 2 game phases in Kiswahili variant - will be ignored by other variants
        $sql = "INSERT INTO kvstore(`key`, value_text) VALUES ('phase', '1st')";
        self::DbQuery($sql);
        // stateAfterMove is used to set state after move execution to use in next state
        $sql = "INSERT INTO kvstore(`key`, value_text) VALUES ('stateAfterMove', 'nextPlayer')";
        self::DbQuery($sql);
        // captureField persists the last field number which allowed a capture to use in later move
        $sql = "INSERT INTO kvstore(`key`, value_number) VALUES ('captureField', 0)";
        self::DbQuery($sql);
        // moveDirection persists the last move direction to keep it in further moves
        $sql = "INSERT INTO kvstore(`key`, value_number) VALUES ('moveDirection', 0)";
        self::DbQuery($sql);
        // kutakatiaMoves persists the moves yet to make in kutakatia until normal move mode is active again - will be ignored for variants other than Kiswahili
        $sql = "INSERT INTO kvstore(`key`, value_number) VALUES ('kutakatiaMoves', 0)";
        self::DbQuery($sql);
        // blockedField persists a field blocked by kutakatia - will be ignored for variants other than Kiswahili
        $sql = "INSERT INTO kvstore(`key`, value_number) VALUES ('blockedField', 0)";
        self::DbQuery($sql);
        // blockedPlayer persists the player id of the player who's field ist blocked by kutakatia - will be ignored for variants other than Kiswahili
        $sql = "INSERT INTO kvstore(`key`, value_number) VALUES ('blockedPlayer', 0)";
        self::DbQuery($sql);
        // nyumba5functional is a flag if player 1 still owns a functional nyumba (field 5) - will be ignored for variants other than Kiswahili
        $sql = "INSERT INTO kvstore(`key`, value_boolean) VALUES ('nyumba5functional', true)";
        self::DbQuery($sql);
        // nyumba4functional is a flag if player 2 still owns a functional nyumba (field 4) - will be ignored for variants other than Kiswahili
        $sql = "INSERT INTO kvstore(`key`, value_boolean) VALUES ('nyumba4functional', true)";
        self::DbQuery($sql);
        // editDone is a flag if the editor nees to be opened (only if option was selected)
        $sql = "INSERT INTO kvstore(`key`, value_boolean) VALUES ('editDone', '$editDone')";
        self::DbQuery($sql);
        // gamelog is full official game log text, empty at start
        $sql = "INSERT INTO kvstore(`key`, value_text) VALUES ('gamelog', '')";
        self::DbQuery($sql);
        // moveNo is number for gamelog
        $sql = "INSERT INTO kvstore(`key`, value_number) VALUES ('moveNo', 0)";
        self::DbQuery($sql);
        // lastLogPlayer is player id of player who logged last to see if a move was already logged
        $sql = "INSERT INTO kvstore(`key`, value_number) VALUES ('lastLogPlayer', 0)";
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
        self::trace('*** getAllDatas was called ***');
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_no no FROM player";
        $result['players'] = self::getCollectionFromDb($sql);

        $sql = "SELECT player player, field no, stones count FROM board";
        $result['board'] = self::getObjectListFromDB($sql);

        $result['nyumba_'.$current_player_id] = $this->checkForNyumbaState($current_player_id);
        $opponent_id = self::getPlayerAfter($current_player_id);
        $result['nyumba_'.$opponent_id] = $this->checkForNyumbaState($opponent_id);

        $result['variant'] = $this->getVariant();

        $result['seed_selection'] = $this->getGameStateValue('seed_selection');

        $sql = "SELECT value_text from kvstore where `key` = 'gamelog'";
        $result['gamelog'] = $this->getUniqueValueFromDB($sql);

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
        // Since usually the game ends by opponent having no stones left in 1st row, we calculate current progression
        // as inverse procentual ratio of smaller and bigger first row count;
        // note: since this is not linear, but will go in both directions, the value may rise and fall
        $board = $this->getBoard();
        $player = self::getActivePlayerId();
        $playerCount = $this->getFirstRowCount($player, $board);
        $opponent = self::getPlayerAfter($player);
        $opponentCount = $this->getFirstRowCount($opponent, $board);
        $minCount = min($playerCount, $opponentCount);
        $maxCount = max($playerCount, $opponentCount);
        $ratio = $maxCount == 0 ? 0 : $minCount / $maxCount;
        return (1 - $ratio) * 100;
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    // Encapsulate game variant check to also consider 2nd phase of Kiswahili
    function getVariant()
    {
        $sql = "SELECT value_text FROM kvstore WHERE `key` = 'phase'";
        $phase = $this->getUniqueValueFromDB($sql);
        $variant = $this->getGameStateValue('game_variant');

        return $phase === "2nd" ? VARIANT_KISWAHILI_2ND : $variant;
    }

    // Get the complete board with a double associative array player/no -> count
    function getBoard()
    {
        $sql = "SELECT player player, field no, stones count, stones countBackup FROM board";
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
        // either 5 (for 1st player) or 4 (for 2nd player)
        $sql = "SELECT player_no FROM player WHERE player_id = '$player_id'";
        $playerNo = self::getUniqueValueFromDB($sql);

        return $playerNo == 1 ? 5 : 4;
    }

    // Hus variant: Possible bowls and their direction of a player's bowls with at least 2 stones
    function getHusPossibleMoves($player_id)
    {
        self::trace('*** getHusPossibleMoves was called with parameter player_id='.$player_id.' ***');
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
        self::trace('*** getMtajiPossibleCaptures was called with parameter player_id='.$player_id.' ***');
        $result = array();

        $board = $this->getBoard();
        $opponent = self::getPlayerAfter($player_id);

        // check if in kutakatia state for Kiswahili 2nd phase
        $kutakatiaMoves = 0;
        $blockedField = 0;
        $blockedPlayer = 0;
        if ($this->getVariant() == VARIANT_KISWAHILI_2ND) {
            $sql = "SELECT value_number FROM kvstore WHERE `key` = 'kutakatiaMoves'";
            $kutakatiaMoves = $this->getUniqueValueFromDB($sql);
            $sql = "SELECT value_number FROM kvstore WHERE `key` = 'blockedField'";
            $blockedField = $this->getUniqueValueFromDB($sql);
            $sql = "SELECT value_number FROM kvstore WHERE `key` = 'blockedPlayer'";
            $blockedPlayer = $this->getUniqueValueFromDB($sql);
        }

        // check if any bowl with at least 2 and at most 15 stones leads to a harvest in initial move
        for ($i = 1; $i<= 16; $i++) {
            $count = $board[$player_id][$i]["count"];
            if ($count >=2 && $count <= 15) {
                // will take left and/or right move if possible
                $subResult = array();

                // check if move to the left gets to 1st row, lands in non-empty bowl and has an adjacent filled bowl
                $destinationField = $this->getDestinationField($i, -1, $count);
                if ($destinationField <= 8 && $board[$player_id][$destinationField]["count"] > 0 &&
                    $board[$opponent][$destinationField]["count"] > 0) {
                    $left = $i == 1 ? 16 : $i - 1;
                    // in kutakatia move mode, only the field which captures the blocked field of opponent should be added
                    if ($kutakatiaMoves == 0 || ($kutakatiaMoves > 0 && $blockedPlayer == $opponent && $blockedField == $destinationField)) {
                        array_push($subResult, $left);
                    }
                }

                // check if move to the right gets to 1st, lands in non-empty bowl row and has an adjacent filled bowl
                $destinationField = $this->getDestinationField($i, 1, $count);
                if ($destinationField <= 8 && $board[$player_id][$destinationField]["count"] > 0 &&
                    $board[$opponent][$destinationField]["count"] > 0) {
                    $right = $i == 16 ? 1 : $i + 1;
                    // in kutakatia move mode, only the field which captures the blocked field of opponent should be added
                    if ($kutakatiaMoves == 0 || ($kutakatiaMoves > 0 && $blockedPlayer == $opponent && $blockedField == $destinationField)) {
                        array_push($subResult, $right);
                    }
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
        self::trace('*** getMtajiPossibleNonCaptures was called with parameter player_id='.$player_id.' ***');
        $result = array();

        $board = $this->getBoard();

        // check if in kutakatia state for Kiswahili 2nd phase
        $kutakatiaMoves = 0;
        $blockedField = 0;
        $blockedPlayer = 0;
        if ($this->getVariant() == VARIANT_KISWAHILI_2ND) {
            $sql = "SELECT value_number FROM kvstore WHERE `key` = 'kutakatiaMoves'";
            $kutakatiaMoves = $this->getUniqueValueFromDB($sql);
            $sql = "SELECT value_number FROM kvstore WHERE `key` = 'blockedField'";
            $blockedField = $this->getUniqueValueFromDB($sql);
            $sql = "SELECT value_number FROM kvstore WHERE `key` = 'blockedPlayer'";
            $blockedPlayer = $this->getUniqueValueFromDB($sql);
        }

        // first check if any bowl in the 1st row has at least 2 stones, then in the 2nd row
        for ($i = 1; $i <= 8; $i++) {
            // exclude blocked bowl if there is one (active player is the one being blocked)
            if ($kutakatiaMoves == 0 || !($kutakatiaMoves > 0 && $player_id == $blockedPlayer && $i == $blockedField)) {
                if ($board[$player_id][$i]["count"] >= 2 && !($player_id == $blockedPlayer && $i == $blockedField)) {
                    $left = $i == 1 ? 16 : $i - 1;
                    $right = $i + 1;
                    $result[$i] = array($left, $right);
                }
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
        self::trace('*** getPossibleKichwas was called with parameter player_id='.$player_id.' ***');
        $result = array();

        $opponent = $this->getPlayerAfter($player_id);

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
            $result[1] = array(2, $opponent.'_'.$captureField);
        } 
        // right kichwa has to be chosen if capture happens in right kichwa or kimbi
        // or direction from previous move was already counterclockwise without capture in left kichwa or kimbi
        elseif ($captureField >= 7 || ($moveDirection == -1 && $captureField > 2)) {
            $result[8] = array(7, $opponent.'_'.$captureField);
        }
        // both kichwas may be chosen if capture happens in middle and direction is not yet set
        elseif ($moveDirection == 0 && $captureField > 2 && $captureField < 7) {
            $result[1] = array(2, $opponent.'_'.$captureField);
            $result[8] = array(7, $opponent.'_'.$captureField);
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
        self::trace('*** getKunamuaPossibleCaptures was called with parameter player_id='.$player_id.' ***');
        $result = array();

        $board = $this->getBoard();
        $opponent = self::getPlayerAfter($player_id);

        // check if any non-empty bowl in the 1st row has oposite stones
        for ($i = 1; $i<= 8; $i++) {
            $countPlayer = $board[$player_id][$i]["count"];
            $countopponent = $board[$opponent][$i]["count"];

            //  add to result if a harvest move was found, direction is not yet set
            if ($countPlayer >= 1 && $countopponent >= 1) {
                $result[$i] = array(0);
            }
        }

        return $result;
    }

    // kunamua phase step #2: if no harvest move was found, check for non-harvest moves,
    // assumes that before the caputure step was checked, so that there is no capture possible
    function getKunamuaPossibleNonCaptures($player_id)
    {
        self::trace('*** getKunamuaPossibleNonCaptures was called with parameter player_id='.$player_id.' ***');
        $result = array();

        $board = $this->getBoard();
        $nyumba = $this->getNyumba($player_id);
        $nyumbaPossesssion = $this->checkForNyumbaPossession($nyumba, $player_id, $board);
        $nyumbaFunctional = $this->checkForFunctionalNyumba($nyumba, $player_id, $board);
        // in case of not posessing nyumba, a bowl with only 1 stone can only used if none with more exists
        if (!$nyumbaPossesssion) {
            // 1st assume that there are bowls with more than 1 stone and choose these
            for ($i = 1; $i <= 8; $i++) {
                if ($board[$player_id][$i]["count"] >= 2) {
                    $left = $i == 1 ? 16 : $i - 1;
                    $right = $i + 1;
                    $result[$i] = array($left, $right);
                }
            }
            // then only if none was found, allow bowls with only 1 stone
            if (empty($result)) {
                for ($i = 1; $i <= 8; $i++) {
                    if ($board[$player_id][$i]["count"] >= 1) {
                        $left = $i == 1 ? 16 : $i - 1;
                        $right = $i + 1;
                        $result[$i] = array($left, $right);
                    }
                }
            }
        // in case of a functional nyumba, only take this, if it's the only non-empty bowl
        } else if ($nyumbaFunctional) {
            // check all bowls in the 1st row for stones
            for ($i = 1; $i <= 8; $i++) {
                // skip functional nyumba for now
                if ($i != $nyumba && $board[$player_id][$i]["count"] >= 1) {
                    $left = $i == 1 ? 16 : $i - 1;
                    $right = $i + 1;
                    $result[$i] = array($left, $right);
                }
            }
            // only add functional nyumba now if no other was found
            if (empty($result) && $board[$player_id][$nyumba]["count"] >= 1) {
                $left = $nyumba - 1;
                $right = $nyumba + 1;
                $result[$nyumba] = array($left, $right);
            }
        // in case of non-functional nyumba, take all non-empty bowls
        } else {
            // check all bowls in the 1st row for stones
            for ($i = 1; $i <= 8; $i++) {
                if ($board[$player_id][$i]["count"] >= 1) {
                    $left = $i == 1 ? 16 : $i - 1;
                    $right = $i + 1;
                    $result[$i] = array($left, $right);
                }
            }
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

    // Check if a player still owns the nyumba
    // in possession means, having not destroyed it, even if less than 6 seeds
    function checkForNyumbaPossession($nyumba, $player_id)
    {
        self::trace('*** checkForNyumbaPossession was called with parameter nyumba='.$nyumba.',player_id='.$player_id.' ***');
        $key = "nyumba".$nyumba."functional";
        $sql = "SELECT value_boolean FROM kvstore WHERE `key` = '$key'";
        $hasNyumba = self::getUniqueValueFromDB($sql);
        
        return $hasNyumba;
    }

    // Check if a player still owns a functional nyumba
    // functional means, having not destroyed it and 6 or more seeds
    function checkForFunctionalNyumba($nyumba, $player_id, $board)
    {
        self::trace('*** checkForFunctionalNyumba was called with parameter nyumba='.$nyumba.',player_id='.$player_id.' ***');
        $hasNyumba = $this->checkForNyumbaPossession($nyumba, $player_id);
        
        // database only says that it is not yet destroyed, so check also for enough seeds
        // (which can be less than six even for exisitng nyumba when being temporarily non-functional)
        return $hasNyumba && $board[$player_id][$nyumba]["count"] >= 6;
    }

    // Check for nyumba state for displaying in player info
    // 0: functional (never emptied and >= 6 seeds)
    // 1: non-functional (never emptied and < 6 seeds)
    // 2: destroyed (emptied)
    function checkForNyumbaState($player_id)
    {
        self::trace('*** checkForNyumbaState was called with parameter player_id='.$player_id.' ***');
        $board = $this->getBoard();
        $nyumba = $this->getNyumba($player_id);
        $nyumbaState = !$this->checkForNyumbaPossession($nyumba, $player_id) ? 2 : 
            (!$this->checkForFunctionalNyumba($nyumba, $player_id, $board) ? 1 : 0);
        return $nyumbaState;
    }

     // Check if a player has produced a kutakatia situation for his oponent
     // where after a move without harvest exactly one harvest would be possible for next round
     // and this is neither the opponent's functional nyumba, nor his only filled bowl or bowl 
     // with at least 2 stones in 1st row and the opponent can only do a move without harvest,
     // persist blocked field as it gets used in next 2 moves
     function checkAndMarkKutakatia($player_id, $board)
     {
        self::trace('*** checkAndMarkKutakatia was called with parameter player_id='.$player_id.' ***');
        // determine all criteria in one loop through 1st row and store in separate arrays
        $possiblePlayerCaptures = $this->getMtajiPossibleCaptures($player_id);
        $opponent = $this->getPlayerAfter($player_id);
        $nyumbaOpponent = $this->getNyumba($opponent);
        $possibleOpponentsCaptures = $this->getMtajiPossibleCaptures($opponent);
        $bowlsOpponentWith1Stone = array();
        $bowlsOpponentWith2Stones = array();
        for ($i=1; $i <= 8; $i++) { 
            if($board[$opponent][$i]["count"] >= 1) {
                array_push($bowlsOpponentWith1Stone, $i);
            }
            if($board[$opponent][$i]["count"] >= 2) {
                array_push($bowlsOpponentWith2Stones, $i);
            }
        }

        // check possible captures if only 1 bowl can be captured, even from different bowls
        $possibleCapturedFields = array();
        foreach ($possiblePlayerCaptures as $captureStart => $captureMove) {
            $captureDirection = reset($captureMove) - $captureStart;
            // correct direction if overflown
            $captureDirection = (abs($captureDirection) > 1) ? $captureDirection / -15 : $captureDirection;
            $captureStartStones = $board[$player_id][$captureStart]["count"];
            $currentField = $captureStart;
            for ($i=1; $i<= $captureStartStones; $i++) {
                $captureField = $this->getNextField($currentField, $captureDirection);
                $currentField = $captureField;
            }
            array_push($possibleCapturedFields, $captureField);
        }
        // clear duplicates, array then holds destination fields of capture which has to be only 1 for kutakatia
        $possibleCapturedFields = array_unique($possibleCapturedFields);

        // kutakatia is only possible when exactly one bowl is subject to capture for player next move
        // and opponent cannot capture
        $kutakatia = false;
        if (count($possibleCapturedFields) == 1 && count($possibleOpponentsCaptures) == 0) {
            // take the only possible one
            $field = $possibleCapturedFields[0];

            // 3 other possible exclusions for blocking opponent's field:
            // - not the functional nyumba
            // - not the only non-empty bowl in 1st row
            // - not the only bowl with at least 2 stones in 1st row
            if (!($this->checkForFunctionalNyumba($nyumbaOpponent, $opponent, $board) && $field == $nyumbaOpponent)
                && !(count($bowlsOpponentWith1Stone) == 1 && $field == $bowlsOpponentWith1Stone[0])
                && !(count($bowlsOpponentWith2Stones) == 1 && $field == $bowlsOpponentWith2Stones[0])) {
                    // persist blocked field and player and set kutakatia mode for next 3 player changes (which makes one move per player)
                    $sql = "UPDATE kvstore SET value_number = 3 WHERE `key` = 'kutakatiaMoves'";
                    self::DbQuery($sql);                
                    $sql = "UPDATE kvstore SET value_number = $field WHERE `key` = 'blockedField'";
                    self::DbQuery($sql);
                    $sql = "UPDATE kvstore SET value_number = $opponent WHERE `key` = 'blockedPlayer'";
                    self::DbQuery($sql);
                    $kutakatia = true;
            }
        }

        return $kutakatia;
     }
 
     // check if nyumba was captured and thereby has to be marked as destroyed
    function checkAndMarkDestroyedNyumba($player_id, $field)
    {
        self::trace('*** checkAndMarkDestroyedNyumba was called with parameter player_id='.$player_id.',field='.$field.' ***');
        $nyumba = $this->getNyumba($player_id);
        if ($field == $nyumba) {
            $key = "nyumba".$nyumba."functional";
            $sql = "UPDATE kvstore SET value_boolean = false WHERE `key` = '$key'";
            self::DbQuery($sql);
        }
    }

    // Calculate player's stones in first row
    function getFirstRowCount($player_id, $board)
    {
        // first check if the player can still move and sum up stones
        $sum = 0;
        for ($i = 1; $i <= 8; $i++) {
            $count = $board[$player_id][$i]["count"];
            $sum += $count;
        }

        return $sum;
    }

    // Add and save game log entry and move number;
    // special cases for safari and kutakatia, since this adds a character to the log
    function addToGamelog($player_id, $text, $isSafari, $isKutakatia) {
        $sql = "SELECT value_number from kvstore where `key` = 'moveNo'";
        $moveNo = $this->getUniqueValueFromDB($sql);
        $sql = "SELECT value_text from kvstore where `key` = 'gamelog'";
        $moveText = $this->getUniqueValueFromDB($sql);

        $sql = "SELECT player_no from player where player_id = $player_id";
        $playerNo = $this->getUniqueValueFromDB($sql);
        if ($playerNo == 1) {
            // safari/kutakatia for 1st player just adds +/*
            if ($isSafari) {
                $moveText = $moveText . '+';
            } elseif ($isKutakatia) {
                $moveText = $moveText . '*';
            } else {
                // 1st player starts new round for regular move
                $moveNo = $moveNo + 1;
                $sql = "UPDATE kvstore SET value_number = $moveNo WHERE `key` = 'moveNo'";
                self::DbQuery($sql);
                $moveText = $moveText . ' ' . $moveNo . ': ' . $text;
            }
        } else {
            // safari/kutakatia for 2nd player has to insert +/* before last ;
            if ($isSafari) {
                $moveText = rtrim($moveText, ';') . '+;';
            } elseif ($isKutakatia) {
                $moveText = rtrim($moveText, ';') . '*;';
            } else {
                $moveText = $moveText . ' ' . $text . ';';
            }
        }
        // final log message for move
        $sql = "UPDATE kvstore SET value_text = '$moveText' WHERE `key` = 'gamelog'";
        self::DbQuery($sql);
        // persist player id to see that log has been done this round
        $sql = "UPDATE kvstore SET value_number = $player_id WHERE `key` = 'lastLogPlayer'";
        self::DbQuery($sql);
    }

    // Map internal notation 1..16 for each player_id to official notation AB/ab 1..8;
    // direction is always from player perspective left/right (not clockwise/counterclockwise) or the kichwa selected;
    // flags for special move notations, for kichwa + means right, - means left independent of side;
    function mapNotation($player_id, $field, $moveDirection, $isKichwa, $isKutakata) {
        $sql = "SELECT player_no from player where player_id = $player_id";
        $playerNo = $this->getUniqueValueFromDB($sql);
        if ($playerNo == 1) {
            // bowl 1..8 is A, 9..16 is B, 1st row stays 1..8, 2nd row has to be inverted and reduced to 1..8
            $mappedNotation = ($field <= 8 ? 'A' . $field : 'B' . abs($field - 17));
            // direction is different in 1st and 2nd row, if kichwa is selected, direction is set
            if ($isKichwa) {
                $direction = ($moveDirection < 0 ? '<' : '>');
            } elseif ($field <= 8) {
                $direction = ($moveDirection < 0 ? '<' : '>');
            } else {
                $direction = ($moveDirection < 0 ? '>' : '<');
            }
        } else {
            // bowl 1..8 is a, 9..16 is b, 1st row has to be inverted, 2nd row reduced to 1..8
            $mappedNotation = ($field <= 8 ? 'a' . abs($field - 9) : 'b' . ($field - 8));
            // direction is different in 1st and 2nd row, if kichwa is selected, direction is set
            if ($isKichwa) {
                $direction = ($moveDirection < 0 ? '<' : '>');
            } else if ($field <= 8) {
                $direction = ($moveDirection < 0 ? '>' : '<');
            } else {
                $direction = ($moveDirection < 0 ? '<' : '>');
            }
        }
        $mappedNotation = $mappedNotation . $direction;

        // optionally special case
        // note: winning move is not regarded since game can end with giving up, zombie, tie, opponent move
        if ($isKutakata) {
            $mappedNotation = $mappedNotation . '*';
        } 
        return $mappedNotation;
    }

    // Calculate player's score, which is 0 if lost or sum of fields if not,
    // so this function also checks for end of game
    function getScore($player_id, $board)
    {
        self::trace('*** getScore was called with parameter player_id='.$player_id.' ***');
        // first check if the player can still move and sum up stones
        $sum = 0;
        $canMove = false;
        // distinguish 1st phase of Kiswahili from others to determine condition for 1st line
        if ($this->getVariant() == VARIANT_KISWAHILI) {
            // for kunamua phase of Kiswahili, a bowl with 1 seed in 1st row is sufficient
            for ($i = 1; $i <= 8; $i++) {
                $count = $board[$player_id][$i]["count"];
                $sum += $count;
                if ($count >= 1) {
                    $canMove = true;
                }
            }
            // look at 2nd row just for the sum, does not influence, if player can move
            for ($i = 9; $i <= 16; $i++) {
                $count = $board[$player_id][$i]["count"];
                $sum += $count;
            }
        } else {
            // for all other variants or 2nd phase of Kiswahili determine condition in both lines
            for ($i = 1; $i <= 16; $i++) {
                $count = $board[$player_id][$i]["count"];
                $sum += $count;
                // there has to be at least one bowl with at least two stones
                if ($count >= 2) {
                    $canMove = true;
                }
            }
        }

        // if he can move, check if 1st line is not empty (is already checked for Kiswahili 1st phase above, but does not hurt)
        $isEmpty = true;
        if ($canMove) {
            for ($i = 1; $i <= 8; $i++) {
                if ($board[$player_id][$i]["count"] > 0) {
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

    // calculates scores for both players and notifies players,
    // returns true if game ends
    function updateScores($board, $playerLast, $playerNext) {
        // calculate scores and thereby if someone has lost or is zombie (score = 0)
        $sql = "SELECT player_zombie FROM player WHERE player_id = '$playerLast'";
        $zombie = self::getUniqueValueFromDB($sql);
        $scoreLast = 0;
        if (!$zombie) {
            $scoreLast = $this->getScore($playerLast, $board);
        }
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

        // get game log
        $sql = "SELECT value_text from kvstore where `key` = 'gamelog'";
        $gamelogText = $this->getUniqueValueFromDB($sql);

        // notify players of new scores and nyumba state and game log
        $newScores = array($playerLast => $scoreLast, $playerNext => $scoreNext);
        self::notifyAllPlayers("newScores", "", array(
            "scores" => $newScores,
            "nyumba_".$playerLast => $this->checkForNyumbaState($playerLast),
            "nyumba_".$playerNext => $this->checkForNyumbaState($playerNext),
            "gamelog" => $gamelogText
        ));

        // check for end of game
        if ($scoreLast == 0 || $scoreNext == 0) {
            return true;
        } else {
            return false;
        }
    }
    
    function saveBoard($board) {
        // Note: datastructure is different than when retrieving board from database
        $player1 = self::getActivePlayerId();
        $player1Nyumba = $this->getNyumba($player1);
        $player2 = self::getPlayerAfter($player1);
        $player2Nyumba = $this->getNyumba($player2);
        // assert that full board is present
        if (count($board) != 34) {
            throw new feException("Player data is corrupt, board has not the right size of 34: " . count($board));
        }
        for ($i = 0; $i < 34; $i++) {
            $player = $board[$i]["player"];
            $field = $board[$i]["no"];
            $count = $board[$i]["count"];

            // assert that player data fits players on server
            if ($player != $player1 && $player != $player2) {
                throw new feException("Player data is corrupt, contains player which is not part of the game: " . $player);
            }
            // limit maximum stones per bowl to prevent possible problems later on in the browser
            if ($count > 20) {
                throw new feException("Player data is corrupt, contains more than 20 seeds per pit: " . $count . " in " . $i);
            }

            $sql = "UPDATE board SET stones = $count WHERE player = $player and field = $field";
            self::DbQuery($sql);

            // also store Nyumba state, will be only used in Kiswahili variant
            if ($player == $player1 && $field == $player1Nyumba) {
                $nyumbaFunctional = $count < 6 ? 'false' : 'true';
                $key = 'nyumba'.$field.'functional';
                $sql = "UPDATE kvstore SET value_boolean = $nyumbaFunctional WHERE `key` = '$key'";
                self::DbQuery($sql);
            } else if ($player == $player2 && $field == $player2Nyumba) {
                $nyumbaFunctional = $count < 6 ? 'false' : 'true';
                $key = 'nyumba'.$field.'functional';
                $sql = "UPDATE kvstore SET value_boolean = $nyumbaFunctional WHERE `key` = '$key'";
                self::DbQuery($sql);
            }

            // set phase according to seeds in storage area if in KISWAHILI variant
            // (only upper storage area is checked, since they should both be the same)
            if (($this->getVariant() == VARIANT_KISWAHILI || $this->getVariant() == VARIANT_KISWAHILI_2ND) 
                && $i == 0) {
                $phase = $count == 0 ? "2nd" : "1st";
                $sql = "UPDATE kvstore SET value_text = '$phase' WHERE `key` = 'phase'";
                self::DbQuery($sql);
            }
        }   
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in baolakiswahili.action.php)
    */

    // Main game logic method, handles all moves after user action,
    // player has selected a move (start field and direction field)
    // game modes are distinguished here to exeute it
    // note: to keep logic free from complicated checks of rare edge cases, some official rules are deliberately ignored:
    // 1) no prevention of a move which causes loss of the player
    // 2) no check if a move from kichwa to the outer row is the only filled bowl and contains 16 or more stones
    // 3) no check if kutakatiaed bowl could be reached in later part of a harvest move by the player having done the blocking, must be harvested directly
    function executeMove($player, $field, $direction)
    {
        self::trace('*** executeMove was called with parameters player='.$player.', field='.$field.', direction='.$direction.' ***');
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Action checks and move checks for all variants
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Check that this player is active and that this action is possible at this moment,
        // store action name for further distinguishing
        if ($this->checkAction('executeMove', false)) {
            $currentAction = 'executeMove';
        } elseif ($this->checkAction('selectKichwa', false)) {
            $currentAction = 'selectKichwa';
        } elseif ($this->checkAction('decideSafari', false)) {
            $currentAction = 'decideSafari';
        } else {
            throw new feException("Impossible action: is not executeMove or selectKichwa or decideSafari");
        }
        
        // Distinguish game mode for move check
        if ($this->getVariant() == VARIANT_KISWAHILI) {
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
            } elseif ($currentAction == 'decideSafari') {
                if ($direction != 0 && $direction != 1) {
                    throw new feException("Impossible move: direction not possible in safari decision move");
                }

                // if stop move was selected, quit execution and go to next state
                if ($direction == 0) {
                    // persist planned state after move and possible capture field in database for using in stNextPlayer
                    $sql = "UPDATE kvstore SET value_text = 'nextPlayer' WHERE `key` = 'stateAfterMove'";
                    self::DbQuery($sql);

                    $this->gamestate->nextState('executeMove');
                    return;
                }
            }
        } elseif ($this->getVariant() == VARIANT_KUJIFUNZA || $this->getVariant() == VARIANT_KISWAHILI_2ND) {
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
        } elseif ($this->getVariant() == VARIANT_HUS) {
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
        $opponent = $this->getPlayerAfter($player);
        $players = array($player, $opponent);
        $selectedField = $field;
        $sourceField = $field;
        $board = $this->getBoard();
        $rounds = 0;

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
        // emptyopponent: empty opponent's field
        // moveActive: move own stones to field
        // move opponent: move opponent's captured stones to field
        // placeActive: place a new stone from storage into field
        // taxActive: extract 2 stones only from nyumba
        $moves = array();

        // for statistics - total number of stones moved from player
        $overallMoved = 0;
        // for statistics - total number of stolen stones from opponent
        $overallStolen = 0;
        // for statistics - total number of bowls emtied from opponent
        $overallEmptied = 0;

        // Distinguish game mode for move execution
        if ($this->getVariant() == VARIANT_KISWAHILI && $currentAction == 'executeMove') {
            // 1st preserve nyumba state before putting seed
            $nyumba = $this->getNyumba($player);
            $wasNyumbaFunctional = $this->checkForFunctionalNyumba($nyumba, $player, $board);

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

                // gamelog is not possible yet, since direction is not set, will be done with kichwa selection
            } else {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kiswahili variant - non-capture move
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // distinguish between taxing nyumba and regular move
                if ($sourceField == $nyumba && $wasNyumbaFunctional) {
                    // functional nyumba is not emptied, but has to be taxed which means extracting only 2 seeds
                    $count = 2;
                    $board[$player][$sourceField]["count"] -= $count;
                    array_push($moves, "taxActive_" . $sourceField);
                    $overallMoved += $count;
                } else {
                    // this is a non-capture move, no further captures are allowed, move only continues in own two rows,
                    // first empty start field
                    $board[$player][$sourceField]["count"] = 0;
                    array_push($moves, "emptyActive_" . $sourceField);
                    $overallMoved += $count;
                    // check if emptying destroyed nyumba
                    $this->checkAndMarkDestroyedNyumba($player, $sourceField);
                }

                // log move in official notation
                $this->addToGamelog($player, $this->mapNotation($player, $field, $moveDirection, false, true), false, false);

                // only continue if not lost yet (e.g. by emtpying last own bowl in 1st row),
                // which can only happen when emptying a kichwa, thus check for this source
                $score = $this->getScore($player, $board);
                if ($score == 0 && ($sourceField == 1 || $sourceField == 8)) {
                    // make 1 more move to show loss condition visibly
                    $destinationField = $this->getNextField($sourceField, $moveDirection);
                    $board[$player][$destinationField]["count"] += 1;
                    array_push($moves, "moveActive_" . $destinationField);
                    $sourceField = $destinationField;

                } else {
                    // make moves until last field was empty before putting stone
                    $rounds = 0;
                    $startField = $sourceField;
                    while ($count > 1) {
                        // distribute stones in the next fields in selected direction until last one
                        while ($count > 0) {
                            // calculate next field to move to and leave 1 stone
                            $destinationField = $this->getNextField($sourceField, $moveDirection);
                            if ($destinationField == $startField) {
                                // move travelled a full round
                                $rounds += 1;
                            }
                            $board[$player][$destinationField]["count"] += 1;
                            array_push($moves, "moveActive_" . $destinationField);
                            $sourceField = $destinationField;
                            $count -= 1;
                        }

                        // protection for infinite moves: stop move after 12 rounds
                        // by converting the player into a zombie :-)
                        if ($rounds >= 12) {
                            $sql = "UPDATE player SET player_zombie = true WHERE player_id ='$player'";
                            self::DbQuery($sql);
                            break;
                        }

                        // source field now points to field of last put stone
                        $count = $board[$player][$sourceField]["count"];

                        // empty own bowl for next move if it ends in non-empty bowl which was not a functional nyumba at move start
                        if ($count > 1) {
                            $nyumba = $this->getNyumba($player);
                            if ($sourceField == $nyumba && $wasNyumbaFunctional) {
                                // clear count to leave loop
                                $count = 0;
                            } else {
                                $board[$player][$sourceField]["count"] = 0;
                                array_push($moves, "emptyActive_" . $sourceField);
                                $overallMoved += $count;
                            }
                        }
                    }
                }
            }
        } elseif (($this->getVariant() == VARIANT_KUJIFUNZA || $this->getVariant() == VARIANT_KISWAHILI_2ND) 
            && $currentAction == 'executeMove') {
            // get stones for move
            $count = $board[$player][$sourceField]["count"];

            // empty start field
            $board[$player][$sourceField]["count"] = 0;
            array_push($moves, "emptyActive_" . $sourceField);
            $overallMoved += $count;

            // distinguish capture and non-capture move
            if (!empty($possibleCaptures)) {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kujifunza variant or 2nd phase Kiswahili variant - capture move
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // log move in official notation
                $this->addToGamelog($player, $this->mapNotation($player, $field, $moveDirection, false, false), false, false);

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
    /// Kujifunza variant or 2nd phase Kiswahili variant - non-capture move
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // only continue if not lost yet, which happens if emtpying kichwa which is last own bowl in 1st row
                // and moving to the outside
                $score = $this->getScore($player, $board);
                $destinationField = $this->getNextField($sourceField, $moveDirection);
                if ($score == 0 && (($sourceField == 1 && $destinationField == 16)
                    || ($sourceField == 8 && $destinationField == 9))) {
                    // make 1 more move with all stones to show loss condition visibly
                    $board[$player][$destinationField]["count"] += $count;
                    array_push($moves, "moveActive_" . $destinationField);
                    $sourceField = $destinationField;
                } else {
                    // this is a non-capture move, no further captures are allowed, move only continues in own two rows,
                    // make moves until last field was empty before putting stone
                    $rounds = 0;
                    $startField = $sourceField;
                    while ($count > 1) {
                        // check if nyumba was emptied by move for Kiswahili variant 2nd phase
                        if ($this->getVariant() == VARIANT_KISWAHILI_2ND) {
                            $this->checkAndMarkDestroyedNyumba($player, $sourceField);
                        }

                        // distribute stones in the next fields in selected direction until last one
                        while ($count > 0) {
                            // calculate next field to move to and leave 1 stone
                            $destinationField = $this->getNextField($sourceField, $moveDirection);
                            if ($destinationField == $startField) {
                                // move travelled a full round
                                $rounds += 1;
                            }
                            $board[$player][$destinationField]["count"] += 1;
                            array_push($moves, "moveActive_" . $destinationField);
                            $sourceField = $destinationField;
                            $count -= 1;
                        }

                        // protection for infinite moves: stop move after 12 rounds
                        // by converting the player into a zombie :-)
                        if ($rounds >= 12) {
                            $sql = "UPDATE player SET player_zombie = true WHERE player_id ='$player'";
                            self::DbQuery($sql);
                            break;
                        }

                        // source field now points to field of last put stone
                        $count = $board[$player][$sourceField]["count"];

                        // check if move stops for some reason for Kiswahili variant 2nd phase
                        if ($this->getVariant() == VARIANT_KISWAHILI_2ND) {
                            // check if move stops due to still possessing nyumba
                            $nyumba = $this->getNyumba($player);
                            $nyumbaPossession = $this->checkForNyumbaPossession($nyumba, $player, $board);
                            if ($sourceField == $nyumba && $nyumbaPossession) {                                                                // leave loop to stop move
                                break;
                            }

                            // check if move stops due to kutakatiaed bowl
                            $sql = "SELECT value_number FROM kvstore WHERE `key` = 'blockedField'";
                            $blockedField = $this->getUniqueValueFromDB($sql);
                            $sql = "SELECT value_number FROM kvstore WHERE `key` = 'blockedPlayer'";
                            $blockedPlayer = $this->getUniqueValueFromDB($sql);
                            if ($blockedPlayer == $player && $blockedField == $sourceField) {
                                // leave loop to stop move
                                break;
                            }
                        }

                        // empty own bowl for next move if it ends in non-empty bowl
                        if ($count > 1) {
                            $board[$player][$sourceField]["count"] = 0;
                            array_push($moves, "emptyActive_" . $sourceField);
                            $overallMoved += $count;
                        }
                    }
                }

                // log move in official notation
                $this->addToGamelog($player, $this->mapNotation($player, $field, $moveDirection, false, true), false, false);
            }
        } elseif (($this->getVariant() == VARIANT_KISWAHILI || $this->getVariant() == VARIANT_KUJIFUNZA || $this->getVariant() == VARIANT_KISWAHILI_2ND) 
            && ($currentAction == 'selectKichwa' || $currentAction == 'decideSafari')) {
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kiswahili or Kujifunza variant - kichwa selection or safari
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            // distinguish between continuing after safari and initial kichwa selection
            if ($currentAction == 'decideSafari') {
                // correct fields to select nyumba and previous direction,
                $sourceField = $this->getNyumba($player);
                $count = $board[$player][$sourceField]["count"];
                $sql = "SELECT value_number FROM kvstore WHERE `key` = 'moveDirection'";
                $moveDirection = $this->getUniqueValueFromDB($sql);

                // add to log in official notation
                $this->addToGamelog($player, '', true, false);

                // empty own bowl for next move
                $board[$player][$sourceField]["count"] = 0;
                array_push($moves, "emptyActive_" . $sourceField);
                $overallMoved += $count;

                // set nyumba destroyed
                $this->checkAndMarkDestroyedNyumba($player, $sourceField);
            } else {
                // get stones for move
                $count = $board[$player][$sourceField]["count"];

                // kichwa after capture is selected, first get capture field from previous move and its content
                $sql = "SELECT value_number FROM kvstore WHERE `key` = 'captureField'";
                $captureField = $this->getUniqueValueFromDB($sql);
                $captureCount = $board[$opponent][$captureField]["count"];

                // log move in official notation, but only for 1st phase, since in 2nd phase it has already a direction and was logged
                if ($this->getVariant() == VARIANT_KISWAHILI) {
                    // only log if player did not already log this round
                    $sql = "SELECT value_number from kvstore where `key` = 'lastLogPlayer'";
                    $lastLogPlayer = $this->getUniqueValueFromDB($sql);
                    if ($lastLogPlayer != $player) {
                        // dependent on player number, kichwa ids differ, set left (-) or right (+) for direction in log
                        $sql = "SELECT player_no from player where player_id = $player";
                        $playerNo = $this->getUniqueValueFromDB($sql);
                        if ($playerNo == 1) {
                            $logDirection = ($sourceField == 1 ? -1 : 1);
                        } else {
                            $logDirection = ($sourceField == 1 ? 1 : -1);
                        }
                        $this->addToGamelog($player, $this->mapNotation($player, $captureField, $logDirection, true, false), false, false);
                    }
                }

                // start with emptying opponent's bowl
                array_push($moves, "emptyopponent_" . $captureField);
                $overallMoved += $count;
                $overallStolen += $count;
                $overallEmptied += 1;
                $board[$opponent][$captureField]["count"] = 0;

                // set nyumba destroyed if opponent's nyumba was captured
                $this->checkAndMarkDestroyedNyumba($opponent, $captureField);

                // the move takes captured stones and starts with kichwa,
                // as every move always goes to next field, we go back one field (inverted direction) 
                // for start of move (sourceField) in order to have same behaviour later as for regular moves
                $count = $captureCount;
                $sourceField = $this->getNextField($sourceField, $moveDirection * (-1));
            }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// Kiswahili or Kujifunza variant - move after kichwa selection
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

                // check if opponent has lost and stop moves if lost
                $scoreopponent = $this->getScore($opponent, $board);
                if ($scoreopponent == 0) {
                    break;
                }
                
                // source field now points to field of last put stone
                $count = $board[$player][$sourceField]["count"];

                // if move ends in a non-empty bowl, move continues
                if ($count > 1) {
                    // check if move has another capture
                    if ($sourceField <= 8 && $board[$opponent][$sourceField]["count"] > 0) {
                        // prepare for next part of move in different state, since player has to select kichwa
                        $stateAfterMove = 'continueCapture';
                        $captureField = $sourceField;
                        // leave loop to stop move
                        break;
                    }

                    // for Kiswahili kunamua phase check if a functional nyumba would be emptied now
                    if ($this->getVariant() == VARIANT_KISWAHILI) {
                        $nyumba = $this->getNyumba($player);
                        $nyumbaFunctional = $this->checkForFunctionalNyumba($nyumba, $player, $board);
                        // if there is a functional nyumba, move stops here to let player decide
                        if ($sourceField == $nyumba && $nyumbaFunctional) {
                            $stateAfterMove = 'decideSafari';
                            // leave loop to stop move
                            break;
                        }
                    }

                    // if neither capture nor safari decision, empty own bowl for next move
                    $board[$player][$sourceField]["count"] = 0;
                    array_push($moves, "emptyActive_" . $sourceField);
                    $overallMoved += $count;
                }
            } while ($count > 1);
        } elseif ($this->getVariant() == VARIANT_HUS) {
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
                    // empty opponents oposite bowl in 1st row and add to own stones for move,
                    // if empty, nothing changes
                    if ($sourceField <= 8) {
                        $countopponent = $board[$opponent][$sourceField]["count"];
                        if ($countopponent > 0) {
                            // empty and count stones
                            $overallStolen += $countopponent;
                            $overallEmptied += 1;
                            $count += $countopponent;
                            $board[$opponent][$sourceField]["count"] = 0;
                            array_push($moves, "emptyopponent_" . $sourceField);
                            $overallMoved += $count;
                            $overallStolen += $count;
                            array_push($moves, "moveopponent_" . $sourceField);

                            // check if opponent has lost and stop moves if lost
                            $scoreopponent = $this->getScore($opponent, $board);
                            if ($scoreopponent == 0) {
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
        if ($moveDirection > 0) {
            $messageDirection = clienttranslate("clockwise");
        } elseif ($moveDirection < 0) {
            $messageDirection = clienttranslate("counterclockwise");
        } else {
            $messageDirection = clienttranslate("without direction");
        }
        $message = clienttranslate('${player_name} moved ${message_direction_translated} from pit ${selected_field} to pit ${source_field} in total ${overall_moved} seed(s), emptying ${overall_emptied} pit(s) and having stolen ${overall_stolen} seed(s).');
        self::notifyAllPlayers("moveStones", $message, array(
            'i18n' => array('message_direction_translated'),
            'player' => $player,
            'player_name' => self::getActivePlayerName(),
            'opponent' => $opponent,
            'message_direction_translated' => $messageDirection,
            'selected_field' => $selectedField,
            'source_field' => $sourceField,
            'overall_moved' => $overallMoved,
            'overall_emptied' => $overallEmptied,
            'overall_stolen' => $overallStolen,
            'moves' => $moves,
            'board' => $board
        ));

        // check if kutakatia happened for Kiswahili 2nd phase after move without capture
        if ($this->getVariant() == VARIANT_KISWAHILI_2ND && $currentAction == 'executeMove' && empty($possibleCaptures)) {
            if ($this->checkAndMarkKutakatia($player, $board)) {
                // add to log in official notation
                $this->addToGamelog($player, '', false, true);
            }
        }
            
        // persist planned state after move and possible capture field in database for using in stNextPlayer
        $sql = "UPDATE kvstore SET value_text = '$stateAfterMove' WHERE `key` = 'stateAfterMove'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_number = $captureField WHERE `key` = 'captureField'";
        self::DbQuery($sql);
        // persist last move direction to keep it in next move if possible
        $sql = "UPDATE kvstore SET value_number = $moveDirection WHERE `key` = 'moveDirection'";
        self::DbQuery($sql);

        // Go to the next state depending on current
        $this->gamestate->nextState('executeMove');
    }

    function switchEditPlayer($board)
    {
        // Check that this action is possible at this moment
        $this->checkAction('edit', true);

        // Note: datastructure is different than when retrieving board from database
        $this->saveBoard($board);

        // notify players about board edit to refresh
        $message = clienttranslate('${player_name} edited board and switched players.');
        self::notifyAllPlayers("placeStones", $message, array(
            'player_name' => self::getActivePlayerName(),
            'board' => $board
        ));

        $this->gamestate->nextState('switchPlayer');
    }

    function startWithEditedBoard($board)
    {
        // Check that this action is possible at this moment
        $this->checkAction('edit', true);

        // Note: datastructure is different than when retrieving board from database
        $this->saveBoard($board);

        // notify other player about board edit to refresh
        $message = clienttranslate('${player_name} edited board and started game.');
        self::notifyAllPlayers("placeStones", $message, array(
            'player_name' => self::getActivePlayerName(),
            'board' => $board
        ));

        // save end of editing
        $sql = "UPDATE kvstore SET value_boolean = true WHERE `key` = 'editDone'";
        self::DbQuery($sql);                
        $this->gamestate->nextState('stopEditing');
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
        self::trace('*** argKunamuaMoveSelection was called ***');
        // assume capture move 
        $capture = true;
        $player = self::getActivePlayerId();
        $board = $this->getBoard();

        // check if captures are possible
        $result = $this->getKunamuaPossibleCaptures($player);

        if (empty($result)) {
            $capture = false;
            // if not possible it will be a non-capture move
            $result = $this->getKunamuaPossibleNonCaptures($player);

            // check if a functional nyumba is the only possible move in order to activate tax mode
            $nyumba = $this->getNyumba($player);
            $nyumbaFunctional = $this->checkForFunctionalNyumba($nyumba, $player, $board);
            if (!$nyumbaFunctional) {
                // even if the nyumba is the only possible bowl, it will not be taxed but treated as every other bowl
                $onlyNyumba = false;
            } else {
                // assume that the nyumba is the only possible bowl
                $onlyNyumba = true;
                foreach ($result as $key => $value) {
                    // if another bowl was found, the nyumba is not the only possible bowl, thus no taxing
                    if ($key != $nyumba) {
                        $onlyNyumba = false;
                        break;
                    }
                }
            }
        }

        return array(
            'i18n' => array('type_translated'),
            'possibleMoves' => $result,
            'type' => $capture ? "capture" : ($onlyNyumba ? "tax" : "non-capture"),
            'type_translated' => $capture ? clienttranslate("capture") : ($onlyNyumba ? clienttranslate("tax") : clienttranslate("non-capture")),
            'variant' => $this->getVariant()
        );
    }

    function argSafariDecision()
    {
        self::trace('*** argSafariDecision was called ***');
        // no moves currently possible, but put nyumba in possible moves to allow for highlighting, 
        // button selection will be presented to decide for continuing or stopping
        $nyumba = $this->getNyumba(self::getActivePlayerId());
        return array(
            'possibleMoves' => array($nyumba),
            'type' => "safari",
            'variant' => $this->getVariant()
        );
    }

    function argMtajiMoveSelection()
    {
        self::trace('*** argMtajiMoveSelection was called ***');
        // assume capture move
        $capture = true;
        $result = $this->getMtajiPossibleCaptures(self::getActivePlayerId());

        // if not possible do non-capture move
        if (empty($result)) {
            $capture = false;
            $result = $this->getMtajiPossibleNonCaptures(self::getActivePlayerId());
        }

        // check for blocked bowl by kutakatia
        $sql = "SELECT value_number FROM kvstore WHERE `key` = 'kutakatiaMoves'";
        $kutakatiaMoves = $this->getUniqueValueFromDB($sql);
        $sql = "SELECT value_number FROM kvstore WHERE `key` = 'blockedField'";
        $blockedField = $this->getUniqueValueFromDB($sql);
        $sql = "SELECT value_number FROM kvstore WHERE `key` = 'blockedPlayer'";
        $blockedPlayer = $this->getUniqueValueFromDB($sql);

        return array(
            'i18n' => array('type_translated'),
            'possibleMoves' => $result,
            'blockedField' => $blockedField,
            'blockedPlayer' => $blockedPlayer,
            'type' => $kutakatiaMoves != 0 ? "kutakatia" : ($capture ? "capture" : "non-capture"),
            'type_translated' => $kutakatiaMoves != 0 ? clienttranslate("kutakatia") : ($capture ? clienttranslate("capture") : clienttranslate("non-capture")),
            'variant' => $this->getVariant()
        );
    }

    function argCaptureSelection()
    {
        self::trace('*** argCaptureSelection was called ***');
        return array(
            'possibleMoves' => $this->getPossibleKichwas(self::getActivePlayerId()),
            'type' => "kichwa"
        );
    }

    function argHusMoveSelection()
    {
        self::trace('*** argHusMoveSelection was called ***');
        return array(
            'possibleMoves' => $this->getHusPossibleMoves(self::getActivePlayerId()),
            'type' => ""
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
        // enter editor if here for the first time and option was selected
        $sql = "SELECT value_boolean FROM kvstore WHERE `key` = 'editDone'";
        $editDone = self::getUniqueValueFromDB($sql);
        if ($this->getGameStateValue('editor') == EDITOR_ON && !$editDone) {
            $this->gamestate->nextState('startEditing');
        }
        // transit to the correct phase, which is separate for the 3 possible game options
        else {
            // first check if initialized or edited game situation causes an immediate end
            $board = $this->getBoard();
            $player = self::getActivePlayerId();
            $opponent = self::getPlayerAfter($player);
            $endOfGame = $this->updateScores($board, $player, $opponent);
            if ($endOfGame) {
                $this->gamestate->nextState('endGame');
            } else {
                // then switch to the selected game variant
                if ($this->getVariant() == VARIANT_KISWAHILI) {
                    $this->gamestate->nextState('playKiswahili');
                } elseif ($this->getVariant() == VARIANT_KISWAHILI_2ND) {
                    $this->gamestate->nextState('switchPhase');
                } elseif ($this->getVariant() == VARIANT_KUJIFUNZA) {
                    $this->gamestate->nextState('playKujifunza');
                } elseif ($this->getVariant() == VARIANT_HUS) {
                    $this->gamestate->nextState('playHus');
                } else {
                    // error in options, end game
                    $this->gamestate->nextState('endGame');
                }
            }
        }
    }

    function stNextPlayer()
    {
        self::trace('*** stNextPlayer was called ***');
        // get current situation
        $board = $this->getBoard();
        $playerLast = self::getActivePlayerId();
        $playerNext = self::getPlayerAfter($playerLast);

        // calculate scores, save them and notify players,
        // can cause end of game if one player has a score of 0
        $endOfGame = $this->updateScores($board, $playerLast, $playerNext);
        if ($endOfGame) {
            $stateAfterMove = "endGame";
        } else {
            // check for saved state to use
            $sql = "SELECT value_text FROM kvstore WHERE `key` = 'stateAfterMove'";
            $stateAfterMove = self::getUniqueValueFromDB($sql);

            if ($stateAfterMove === "nextPlayer") {
                // check for phase change in Kiswahili variant
                if ($this->getVariant() == VARIANT_KISWAHILI && 
                    $board[$playerLast][0]["count"] == 0 && $board[$playerNext][0]["count"] == 0) {

                    // store new phase and switch to it
                    $sql = "UPDATE kvstore SET value_text = '2nd' WHERE `key` = 'phase'";
                    self::DbQuery($sql);

                    // check score again since it is possible that a player could still play in 1st phase
                    // but will loose in 2nd phase, change state to move to
                    $endOfGame = $this->updateScores($board, $playerLast, $playerNext);
                    if ($endOfGame) {
                        $stateAfterMove = "endGame";
                    } else {
                        $stateAfterMove = "switchPhase";
                    }
                }

                // check if in kutakatia move for Kiswahili 2nd phase
                if ($this->getVariant() == VARIANT_KISWAHILI_2ND) {
                    $sql = "SELECT value_number FROM kvstore WHERE `key` = 'kutakatiaMoves'";
                    $kutakatiaMoves = $this->getUniqueValueFromDB($sql);
                    if ($kutakatiaMoves > 0) {
                        // persist that one round was played in kutakatia
                        $kutakatiaMoves -= 1;
                        $sql = "UPDATE kvstore SET value_number = $kutakatiaMoves WHERE `key` = 'kutakatiaMoves'";
                        self::DbQuery($sql);
                        // reset blocked information if kutakatia ends
                        if ($kutakatiaMoves == 0) {
                            $sql = "UPDATE kvstore SET value_number = 0 WHERE `key` = 'blockedField'";
                            self::DbQuery($sql);
                            $sql = "UPDATE kvstore SET value_number = 0 WHERE `key` = 'blockedPlayer'";
                            self::DbQuery($sql);
                        }
                    }
                }

                // Next player can play and gets extra time
                self::giveExtraTime($playerNext);
                $this->activeNextPlayer();
            }
        }
        $this->gamestate->nextState($stateAfterMove);
    }

    function stEditSwitch()
    {
        $this->activeNextPlayer();
        $this->gamestate->nextState('nextPlayer');
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
        self::trace('*** zombieTurn was called with parameter state='.$state.'", active_player='.$active_player.' ***');
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
        self::trace('*** zombieTurn was called with parameter from_version='.$from_version.' ***');
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        if ($from_version <= '2103132223') {
            // move was split in 2 states and selected field was selected, does not longer exist
            $sql = "ALTER TABLE `player` DROP COLUMN `selected_field`";
            self::applyDbUpgradeToAllDB($sql);

            // new table for storing arbitrary key value pairs
            $sql = "CREATE TABLE IF NOT EXISTS `kvstore` (`key` VARCHAR(20) NOT NULL, `value_text` VARCHAR(100), `value_number` INT, PRIMARY KEY (`key`)) ENGINE = InnoDB";
            self::applyDbUpgradeToAllDB($sql);
        } else if ($from_version <= ' 2203141850') {
            // value_text column of kvstore was increased in size
            $sql = "ALTER TABLE `kvstore` MODIFY `value_text` VARCHAR(10000) DEFAULT ''";
            self::applyDbUpgradeToAllDB($sql);

            // initialize new kvstore values if necessarry (will give incomplete, but functional gamelog for already running games)
            $sql = "INSERT IGNORE INTO kvstore(`key`, value_text) VALUES ('gamelog', '')";
            self::DbQuery($sql);
            $sql = "INSERT IGNORE INTO kvstore(`key`, value_number) VALUES ('moveNo', 0)";
            self::DbQuery($sql);
            $sql = "INSERT IGNORE INTO kvstore(`key`, value_number) VALUES ('lastLogPlayer', 0)";
            self::DbQuery($sql);
        }
    }


    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// Test code for development
    //////////

    // TESTMODE only (set by JS): place stones for test purposes and changes database
    function testmode()
    {
        self::trace('*** TESTMODE was called ***');
        $sql = "SELECT player_id FROM player WHERE player_no = 1";
        $player1 = self::getUniqueValueFromDB($sql);
        $sql = "SELECT player_id FROM player WHERE player_no = 2";
        $player2 = self::getUniqueValueFromDB($sql);

        // save test stones for player 2
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 0");

        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$player2' AND field = 16");
        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$player2' AND field = 15");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 14");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 13");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 12");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 11");
        self::DbQuery("UPDATE board SET stones = 3 WHERE player = '$player2' AND field = 10");
        self::DbQuery("UPDATE board SET stones = 1 WHERE player = '$player2' AND field = 9");

        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 1");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 2");
        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$player2' AND field = 3");
        self::DbQuery("UPDATE board SET stones = 1 WHERE player = '$player2' AND field = 4");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 5");
        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$player2' AND field = 6");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 7");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player2' AND field = 8");

        // save test stones for player 1
        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$player1' AND field = 1");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 2");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 3");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 4");
        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$player1' AND field = 5");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 6");
        self::DbQuery("UPDATE board SET stones = 1 WHERE player = '$player1' AND field = 7");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 8");

        self::DbQuery("UPDATE board SET stones = 2 WHERE player = '$player1' AND field = 16");
        self::DbQuery("UPDATE board SET stones = 1 WHERE player = '$player1' AND field = 15");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 14");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 13");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 12");
        self::DbQuery("UPDATE board SET stones = 3 WHERE player = '$player1' AND field = 11");
        self::DbQuery("UPDATE board SET stones = 1 WHERE player = '$player1' AND field = 10");
        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 9");

        self::DbQuery("UPDATE board SET stones = 0 WHERE player = '$player1' AND field = 0");

        // reset key value store
        $sql = "UPDATE kvstore SET value_text = '2nd' WHERE `key` = 'phase'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_text = 'nextPlayer' WHERE `key` = 'stateAfterMove'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_number = 0 WHERE `key` = 'captureField'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_number = 0 WHERE `key` = 'moveDirection'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_number = 0 WHERE `key` = 'kutakatiaMoves'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_number = 0 WHERE `key` = 'blockedField'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_number = 0 WHERE `key` = 'blockedPlayer'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_boolean = false WHERE `key` = 'nyumba5functional'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_boolean = false WHERE `key` = 'nyumba4functional'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_boolean = false WHERE `key` = 'editDone'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_text = '' WHERE `key` = 'gamelog'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_number = 0 WHERE `key` = 'moveNo'";
        self::DbQuery($sql);
        $sql = "UPDATE kvstore SET value_number = 0 WHERE `key` = 'lastLogPlayer'";
        self::DbQuery($sql);

        // reset state in database
        $sql = "UPDATE global SET global_value = 20 WHERE global_id = 1";
        self::DbQuery($sql);
    }
}
