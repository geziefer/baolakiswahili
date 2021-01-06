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
 * baolakiswahili.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in baolakiswahili_baolakiswahili.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_baolakiswahili_baolakiswahili extends game_view
  {
    function getGameName() 
    {
        return "baolakiswahili";
    }

    function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/

        $this->page->begin_block( "baolakiswahili_baolakiswahili", "circle" );

        list( $player1, $player2 ) = array_keys( $players );

        // create 4 rows with 8 fields, scaling and shifting them according to the board perspective
        self::createLine(1, 99, 100, 105, 40, $player1, False);
        self::createLine(2, 103, 100, 90, 40, $player1, True);
        self::createLine(3, 107, 100, 76, 60, $player2, True);
        self::createLine(4, 112, 100, 58, 78, $player2, False);

        /*
        
        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
        
        */
        
        /*
        
        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 
        

        $this->page->begin_block( "baolakiswahili_baolakiswahili", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array( 
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }
        
        */



        /*********** Do not change anything below this line  ************/
    }
    
    /*
    Put circle in each field of a line and applies shifts and scale.
    */
    function createLine($y, $hor_scale, $ver_scale, $hor_shift, $ver_shift, $player, $up) 
    {
      $field = ($up) ? 1 : 16;
      for( $x=1; $x<=8; $x++ )
      {
          // insert an invisible circle for later clicking with ID according to board in DB
          $this->page->insert_block( "circle", array(
              'PLAYER' => $player,
              'FIELD' => $field,
              'LEFT' => round( ($x-1)*$hor_scale+$hor_shift ),
              'TOP' => round( ($y-1)*$ver_scale+$ver_shift )
          ) );
       $field += ($up) ? +1 : -1;
      }
    }      

  }