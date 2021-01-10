/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BaoLaKiswahili implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * baolakiswahili.js
 *
 * BaoLaKiswahili user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.baolakiswahili", ebg.core.gamegui, {
        constructor: function(){
            console.log('baolakiswahili constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            // place all stones on board
            var number = 1;
            for( var i in gamedatas.board )
            {
                var bowl = gamedatas.board[i];

                for (var j=1; j<=bowl.count; j++)
                {
                    this.addStoneOnBoard( bowl.player, bowl.no, number );
                    number++;
                }

                // update label to display stone count
                dojo.byId( 'label_'+bowl.player+'_'+bowl.no).innerHTML = "<p>"+bowl.count+"</p>";

            }
            
            // click handlers for both possible click situations on bowl
            dojo.query( '.circle' ).connect( 'onclick', this, 'onSelectBowl');
            dojo.query( '.circle' ).connect( 'onclick', this, 'onSelectDirection');

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
            case 'bowlSelection':
                this.updateBowlSelection( args.args.possibleBowls );
                break;

            case 'moveDirection':
                this.updateMoveDirection( args.args.possibleDirections );
                break;
            }

        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */
        addStoneOnBoard: function( player, field, number )
        {
            dojo.place( this.format_block( 'jstpl_stone', {
                no: number,
                left: Math.floor(((Math.random() * 5) - 2) * 5) + 20,
                top: Math.floor(((Math.random() * 5) - 1) * 5) + 25,
                degree: Math.floor((Math.random() * 73) * 5)
            } ) , 'circle_'+player+'_'+field );
        },

        updateBowlSelection: function( possibleBowls )
        {
            // only display for current player
            if( this.isCurrentPlayerActive() )
            {
                // only 1 player in array
                for( var player in possibleBowls )
                {
                    for( var field in possibleBowls[player] )
                    {
                        // every entry in this array is a possible bowl
                        dojo.addClass( 'circle_'+player+'_'+field, 'possibleBowl' );
                    }            
                }
            }
        },

        updateMoveDirection: function( possibleDirections )
        {
            // only display for current player
            if( this.isCurrentPlayerActive() )
            {
                // Remove previously set css markers for possible bowls
                dojo.query( '.possibleBowl' ).removeClass( 'possibleBowl' );

                // only 1 player in array
                for( var player in possibleDirections )
                {
                    for( var field in possibleDirections[player] )
                    {
                        // true entries are directions, the false entry is the selected
                        if (possibleDirections[player][field]) {
                            dojo.addClass( 'circle_'+player+'_'+field, 'possibleDirection' );
                        } 
                        else
                        {
                            dojo.addClass( 'circle_'+player+'_'+field, 'selectedBowl' );
                        }
                    }            
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
       onSelectBowl: function( evt )
       {
           // Stop event propagation
           dojo.stopEvent( evt );

           var params = evt.currentTarget.id.split('_');
           var player = params[1];
           var field = params[2];

           if( ! dojo.hasClass( 'circle_'+player+'_'+field, 'possibleBowl') )
           {
               // This is not a possible move => the click does nothing
               return ;
           }

           // Check that this action is possible at this moment
           if( this.checkAction( 'selectBowl' ) )    
           {            
               this.ajaxcall( "/baolakiswahili/baolakiswahili/selectBowl.html", {
                   player:player,
                   field:field
               }, this, function( result ) {} );
           }  
       },

       onSelectDirection: function( evt )
       {
           // Stop event propagation
           dojo.stopEvent( evt );

           var params = evt.currentTarget.id.split('_');
           var player = params[1];
           var field = params[2];

           if( ! dojo.hasClass( 'circle_'+player+'_'+field, 'possibleDirection'))
           {
               // This is not a possible move => the click does nothing
               return ;
           }

           // Check that this action is possible at this moment
           if( this.checkAction( 'selectDirection' ) )
           {
               this.ajaxcall( "/baolakiswahili/baolakiswahili/selectDirection.html", {
                   player:player,
                   field:field
               }, this, function( result ) {} );
           }
       },

      /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/baolakiswahili/baolakiswahili/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your baolakiswahili.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            dojo.subscribe( 'moveStones', this, "notif_moveStones" );

            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        notif_moveStones: function( notif )
        {
            // Remove previously set css markers for possible directions and selected bowl
            dojo.query( '.possibleDirection' ).removeClass( 'possibleDirection' );
            dojo.query( '.selectedBowl' ).removeClass( 'selectedBowl' );

            // TODO: this is a workaround to see the moves directly
            document.location.reload();            
        },

        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */

   });             
});
