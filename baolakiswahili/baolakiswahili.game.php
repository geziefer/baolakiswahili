<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * BaoLaKiswahili implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * baolakiswahili.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class BaoLaKiswahili extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );        
	}
	
    protected function getGameName( )
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
    protected function setupNewGame( $players, $options = array() )
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
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','32','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here

        $sql = "INSERT INTO board (player, field, stones) VALUES ";
        $values = array();
        list( $player1, $player2 ) = array_keys( $players );
        for ( $i=1; $i<=16; $i++ )
        {
            $values[] = "('$player1', '$i', '2')";
            $values[] = "('$player2', '$i', '2')";
        }
        $sql .= implode( ',', $values );
        self::DbQuery( $sql );

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
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        $sql = "SELECT player player, field no, stones count FROM board ";
        $result['board'] = self::getObjectListFromDB( $sql );            

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
        // TODO: compute and return the game progression

        return 0;
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
        return self::getDoubleKeyCollectionFromDB( $sql );            
    }

    // Get selected field of player from db
    function getSelectedField( $player_id)
    {
        $sql = "SELECT selected_field FROM player WHERE player_id = '$player_id'";
        return self::getUniqueValueFromDB( $sql );
    }

    // Get move direction of player from db (smaller bowl no = -1, higher bowl no = +1)
    function getMoveDirection( $player_id)
    {
        $sql = "SELECT move_direction FROM player WHERE player_id = '$player_id'";
        return self::getUniqueValueFromDB( $sql );
    }

    // Possible bowls to select are all of players bowls with at least 2 stones
    function getPossibleBowls( $player_id )
    {
        $result = array();
        
        $board = self::getBoard();

        for( $i=1; $i<=16; $i++ )
        {
            if( $board[$player_id][$i]["count"] >= 2 )
            {
                $result[$player_id][$i] = $board[$player_id][$i];
            }
        }

        return $result;
    }

    // Possible directions to select are always the previous and next bowl to the selected one
    function getPossibleDirections( $player_id, $selected )
    {
        $result = array();
        $left = $selected == 1 ? 16 : $selected-1;
        $right = $selected == 16 ? 1 : $selected+1;

        $result[$player_id][$left] = true;
        $result[$player_id][$selected] = false;
        $result[$player_id][$right] = true;

        return $result;
    }

    // Calculate next field from given field in given direction (-1 / +1)
    function getNextField( $field, $direction )
    {
        // calculate next field to move to, adapt overflow in field no
        $destinationField = $field + $direction;
        $destinationField = ($destinationField == 0) ? 16 : $destinationField;
        $destinationField = ($destinationField == 17) ? 1 : $destinationField;
        
        return $destinationField;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in baolakiswahili.action.php)
    */

    // player has selected a bowl for his move
    function selectBowl( $player, $field )
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction( 'selectBowl' ); 

        // Check that selection is possible
        $possibleBowls = self::getPossibleBowls( $player );
        if( $possibleBowls[$player][$field] >= 2)
        {
            // Save selected bowl
            $sql = "UPDATE player SET selected_field = $field where player_id = $player";
            self::DbQuery( $sql );

            // Then, go to the next state
            $this->gamestate->nextState( 'selectBowl' );
        } 
        else
        {
            throw new feException( "Impossible move" );
        }
    }

    // player has selected a direction for his move
    function selectDirection( $player, $field ) 
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction( 'selectDirection' ); 

        // Check that selection is possible
        $selected = self::getSelectedField( $player );
        $possibleDirection = self::getPossibleDirections( $player, $selected );
        if( $possibleDirection[$player][$field] )
        {
            // we only want to have -1 or +1, thus correct if overflown
            $moveDirection = ($field - $selected);
            $moveDirection = (abs($moveDirection) > 1) ? $moveDirection / -15 : $moveDirection;
            $sql = "UPDATE player SET move_direction = $moveDirection where player_id = $player";
            self::DbQuery( $sql );

            // Then go to the next state
            $this->gamestate->nextState( 'selectDirection' );
        } 
        else
        {
            throw new feException( "Impossible move" );
        }
    }

    // player has canceled the direction selection
    function cancelDirection( $player, $field ) 
    {
        // Check that this player is active and that this action is possible at this moment
        self::checkAction( 'selectDirection' ); 

        // Check that selection is possible
        $selected = self::getSelectedField( $player );
        if( $selected == $field )
        {
            // delete selection
            $sql = "UPDATE player SET selected_field = NULL where player_id = $player";
            self::DbQuery( $sql );

            // Then go to the next state
            $this->gamestate->nextState( 'cancelDirection' );
        } 
        else
        {
            throw new feException( "Impossible move" );
        }
    }

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
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
            'possibleBowls' => self::getPossibleBowls( self::getActivePlayerId() )
        );
    }

    function argDirectionSelect()
    {
        $field = self::getSelectedField(self::getActivePlayerId() );

        return array(
            'possibleDirections' => self::getPossibleDirections( self::getActivePlayerId(), $field )
        );
    }

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    function stNextMove()
    {
        // get start situation
        $player = self::getActivePlayerId();
        $selectedField = self::getSelectedField( $player );
        $sourceField = $selectedField;
        $direction = self::getMoveDirection( $player );

        // get board data and initialize counter for statistics
        $board = self::getBoard();
        $overallMoved = 0;

        // get stones for move and empty start field
        $count = $board[$player][$sourceField]["count"];
        $board[$player][$sourceField]["count"] = 0;
        $overallMoved += $count;

        // distribute stones in the next fields in selected direction until last one
        while ($count > 0)
        {
            // calculate next field to move to and leave 1 stone
            $destinationField = self::getNextField( $sourceField, $direction );
            $board[$player][$destinationField]["count"] += 1;
            $sourceField = $destinationField;
            $count -= 1;
        }
        
        // save all changed fields and update score
        for ($i=1; $i<=16; $i++)
        {
            $count = $board[$player][$i]["count"];
            $countBackup = $board[$player][$i]["countBackup"];
            if ($count <> $countBackup)
            {
                $sql = "UPDATE board SET stones = '$count' WHERE player = '$player' AND field = '$i'";
                self::DbQuery( $sql );
            }
        }

        // clear selections
        $sql = "UPDATE player SET selected_field = NULL AND move_direction = NULL WHERE player_id = '$player'";
        self::DbQuery( $sql );

        // update statistics
        //self::incStat($overallMoved, "overallMoved", $player);

        // notify other player
        // TODO: returnin board is only a workaround
        self::notifyAllPlayers( "moveStones", clienttranslate( '${player_name} makes move'), array(
            'player' => $player,
            'player_name' => self::getActivePlayerName(),
            'selectedField' => $selectedField,
            'direction' => $direction,
            'board' => $board
        ) );

        // Active next player
        $player_id = self::activeNextPlayer();

        // Go to the next state
        $this->gamestate->nextState( 'nextPlayer' );    
    }
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

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

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
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
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
