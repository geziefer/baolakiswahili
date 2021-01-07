<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BaoLaKiswahili implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * baolakiswahili.action.php
 *
 * BaoLaKiswahili main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/baolakiswahili/baolakiswahili/myAction.html", ...)
 *
 */
  
  
  class action_baolakiswahili extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "baolakiswahili_baolakiswahili";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// TODO: defines your action entry points there

    public function selectBowl()
    {
        self::setAjaxMode();     
        $player = self::getArg( "player", AT_posint, true );
        $field = self::getArg( "field", AT_posint, true );
        $result = $this->game->selectBowl( $player, $field );
        self::ajaxResponse( );
    }

    public function selectDirection()
    {
        self::setAjaxMode();     
        $player = self::getArg( "player", AT_posint, true );
        $field = self::getArg( "field", AT_posint, true );
        $result = $this->game->selectDirection( $player, $field );
        self::ajaxResponse( );
  }

    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  }
  

