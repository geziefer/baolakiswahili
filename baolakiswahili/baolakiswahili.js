/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BaoLaKiswahili implementation : © <Alexander Rühl> <alex@geziefer.de>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

// flag for TESTMODE in development (default = false), enables button to set stones
const TESTMODE = false;
// flag for COLORVARIANCE in development (default = false), enables 2 different stone colors, but will only if no page reload (F5) occurs
const COLORVARIANCE = false;

// game variants
const VARIANT_KISWAHILI = 1;
const VARIANT_KUJIFUNZA = 2;
const VARIANT_HUS = 3;
// 2nd phase of Kiswahili variant
const VARIANT_KISWAHILI_2ND = 4;

// seed selection
const SEEDS_MODERN = 1;
const SEEDS_TRADITIONAL = 2;

// preference values
const PREF_KICHWA_MODE = 100;
const PREF_KICHWA_MODE_MANUAL = 1;
const PREF_KICHWA_MODE_AUTOMATIC = 2;

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
    function (dojo, declare) {
        return declare("bgagame.baolakiswahili", ebg.core.gamegui, {
            constructor: function () {
                console.log('baolakiswahili constructor');
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

            setup: function (gamedatas) {
                console.log("Starting game setup");

                // place all stones on board
                this.fillBoard(gamedatas.board);

                // add container for player side
                for(var player_id in gamedatas.players) {
                    var player_board_div = 'player_board_'+player_id;
                    dojo.place(this.format_block('jstpl_player_side', {
                        player_id: player_id
                    }), player_board_div);
                    var message = (gamedatas.players[player_id].no == 1 ? _('Player is South') : _('Player is North'));
                    dojo.byId('player_side_'+player_id).innerHTML = message;
                }
    
                // add container for nyumba text (only used in KISWAHILI variant)
                for(var player_id in gamedatas.players) {
                    var player_board_div = 'player_board_'+player_id;
                    dojo.place(this.format_block('jstpl_nyumba_message', {
                        player_id: player_id
                    }), player_board_div);
                    // assume destroyed nyumba, set otherwise if so
                    var message = _('Nyumba is destroyed');
                    if (gamedatas['nyumba_' + player_id] == 0) {
                        message = _('Nyumba is functional');
                    } else if (gamedatas['nyumba_' + player_id] == 1) {
                        message = _('Nyumba is not functional');
                    }
                    dojo.byId('nyumba_message_'+player_id).innerHTML = message;
                }

                // show gamelog
                dojo.byId('gamelog_content').innerHTML = gamedatas.gamelog;
    
                // click handler for different click situations on bowl including editor
                dojo.query('.blk_circle').connect('onclick', this, 'onBowl');
                dojo.query('.blk_seed_area').connect('onclick', this, 'onBowl');

                // click handler for preference change by checkbox
                dojo.query('#checkbox_kichwa_mode').connect('onclick', this, 'onPrefCheckbox');

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                // create empty client args and store game variant, board and plyer ids
                this.clientStateArgs = {};
                this.clientStateArgs.variant = gamedatas.variant;
                this.clientStateArgs.board = gamedatas.board;
                this.clientStateArgs.player1 = Object.keys(gamedatas.players)[0];
                this.clientStateArgs.player2 = Object.keys(gamedatas.players)[1];

                // hide preference box if HUS variant
                if (gamedatas.variant == VARIANT_HUS) {
                    dojo.query('#preferences').style('display', 'none');
                    dojo.query('#gamelog').style('display', 'none');
                } else {
                    // set pref checkbox from user preference and connect with change handler for other variants
                    dojo.byId('checkbox_kichwa_mode').checked = (this.prefs[PREF_KICHWA_MODE].value == PREF_KICHWA_MODE_AUTOMATIC) ? true : false;
                    this.setupPreference();
                }

                // hide seed area and and phase/nyumba label and change board if not KISWAHILI variant and don't waste space
                if  (gamedatas.variant == VARIANT_KUJIFUNZA || gamedatas.variant == VARIANT_HUS) {
                    dojo.query('.blk_seed_area').style('display', 'none');
                    dojo.query('#board').removeClass('board-nyumba').addClass('board');
                    dojo.query('#phase_label').style('display', 'none');
                    dojo.query('.blk_nyumba_message').style('display', 'none');
                    dojo.query('#board').style('margin', '0px');
                }

                // change seeds if not default
                if (gamedatas.seed_selection == SEEDS_TRADITIONAL) {
                    dojo.query('.blk_stone').addClass('blk_stone_seed');
                }

                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);

                // save type of move for later usage and if exists
                var type = "";
                if(!!args.args) {
                    type = args.args.type;
                }
                this.clientStateArgs.type = type;

                // change phase label according to parameter from move - only needed in Kiswahili variant
                if(!!args.args && !!args.args.variant) {
                    if (args.args.variant == 1) {
                        dojo.byId('phase_label').innerHTML = _('Kunamua phase');
                    } else if (args.args.variant == 4) {
                        dojo.byId('phase_label').innerHTML = _('Mtaji phase');
                    }
                    this.clientStateArgs.variant = args.args.variant;
                }

                // states are distinguished in onUpdateActionButtons due to client states
                switch (stateName) {
                }

            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                // button only for TESTMODE
                if (TESTMODE) {
                    console.log("TESTMODE active");
                    this.addActionButton('button_testmode', 'TESTMODE', 'onTestmode');
                }

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        // independent of game mode, it always causes the bowls to be styled according to possible moves
                        case 'kunamuaMoveSelection':
                        case 'mtajiMoveSelection':
                        case 'husMoveSelection':
                            this.updateBowlSelection(args.possibleMoves);
                            break;
                        case 'client_directionSelection':
                            // add cancel button to go back to previous state
                            this.addActionButton('button_cancel', _('Cancel'), 'onCancel');

                            // show possible directions
                            this.updateMoveDirection(args.possibleMoves);
                            break;
                        case 'kunamuaCaptureSelection':
                        case 'mtajiCaptureSelection':
                            var count = Object.keys(args.possibleMoves).length;
                            // check for automatic selection, do not use it in replay mode
                            if (count == 1 && this.prefs[PREF_KICHWA_MODE].value == PREF_KICHWA_MODE_AUTOMATIC
                                && typeof g_replayFrom === 'undefined' && !g_archive_mode) {
                                // automatically select the kichwa field and direction
                                var field = Object.keys(args.possibleMoves)[0];
                                var direction = Object.values(args.possibleMoves)[0][0];
                                console.log("Automatic kichwa selection from field " + field + " in direction " + direction);

                                // call server, game mode will be handled there
                                var player = this.getActivePlayerId();
                                this.ajaxcall("/baolakiswahili/baolakiswahili/executeMove.html", {
                                    lock: true,
                                    player: player,
                                    field: field,
                                    direction: direction
                                }, this, function (result) { });
                            } else {
                                // let the user select the kichwa
                                this.updateBowlSelection(args.possibleMoves);
                            }
                            break;
                        case 'safariDecision':
                            // add buttons for safari decisicon, either continue or stop and mark nyumba like for caputred field
                            this.addActionButton('button_safari', _('Go on safari'), 'onGoSafari');
                            this.addActionButton('button_stop', _('Stop move'), 'onStopMove');
                            var field = args.possibleMoves[0];
                            var player = this.getActivePlayerId();
                            dojo.addClass('circle_' + player + '_' + field, 'blk_capturedBowl');
                            break;
                        case 'gameEdit':
                            // add button for leaving game editor and activate clear
                            this.addActionButton('button_clearEditing', _('Clear mode'), 'onClearEditing');
                            this.addActionButton('button_switchEditPlayer', _('Switch player'), 'onSwitchEditPlayer');
                            this.addActionButton('button_finishEditing', _('Start game'), 'onFinishEditing');
                            this.updateEditBowls();
                            break;
                        case 'client_gameEditClear':
                            // add button for leaving game editor and activate edit
                            this.addActionButton('button_Editing', _('Edit mode'), 'onEditing');
                            this.addActionButton('button_switchEditPlayer', _('Switch player'), 'onSwitchEditPlayer');
                            this.addActionButton('button_finishEditing', _('Start game'), 'onFinishEditing');
                            this.updateClearBowls();
                            break;
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
            
                Here, you can defines some utility methods that you can use everywhere in your javascript
                script.
            
            */

            // place all stones on board
            fillBoard: function (board) {
                var number = 1;
                for (var i in board) {
                    var bowl = board[i];

                    for (var j = 1; j <= bowl.count; j++) {
                        this.addStoneOnBoard(bowl.player, bowl.no, number);
                        number++;
                    }

                    // update label to display stone count
                    dojo.byId('label_' + bowl.player + '_' + bowl.no).innerHTML = "<p>" + bowl.count + "</p>";
                }
            },

            // place one stone in a bowl (1-16) of a player with a little random position within,
            // or put it in storage area if field 0
            addStoneOnBoard: function (player, field, number) {
                // distinguish between storage area and regular bowl to place them in different divs
                if (field == 0) {
                    dojo.place(this.format_block('jstpl_stone', {
                        no: number,
                        left: Math.floor(Math.random() * 38) * 6 + 25,
                        top: Math.floor(Math.random() * 6) * 6 + 5,
                        degree: Math.floor((Math.random() * 73) * 5)
                    }), 'circle_' + player + '_0');
                } else {
                    dojo.place(this.format_block('jstpl_stone', {
                        no: number,
                        left: Math.floor(Math.random() * 6) * 6 + 15,
                        top: Math.floor(Math.random() * 6) * 6 + 15,
                        degree: Math.floor((Math.random() * 73) * 5)
                    }), 'circle_' + player + '_' + field);
                }

                // change stone colors for half the stones to show how they distribute on the board,
                // since a reload (F5) orders them newly, it will not survive that, thus it's not doable for production
                if (COLORVARIANCE && number > 32) {
                    dojo.query('#stone_' + number).addClass('blk_stone_opponent');
                }
            },

            // show all bowls which are selectable
            updateBowlSelection: function (possibleMoves) {
                console.log("Enter updateBowlSelection");

                // only display for current player
                if (this.isCurrentPlayerActive()) {
                    var player = this.getActivePlayerId();
                    // Remove previously set css markers for possible and captured bowls, stones and directions
                    dojo.query('.blk_possibleBowl').removeClass('blk_possibleBowl');
                    dojo.query('.blk_possibleDirection').removeClass('blk_possibleDirection');
                    dojo.query('.blk_selectedBowl').removeClass('blk_selectedBowl');
                    dojo.query('.blk_possibleStone').removeClass('blk_possibleStone');
                    dojo.query('.blk_selectedStone').removeClass('blk_selectedStone');
                    dojo.query('.blk_capturedBowl').removeClass('blk_capturedBowl');

                    // data for active player in array
                    for (var field in possibleMoves) {
                        // every entry in this array is a possible bowl
                        dojo.addClass('circle_' + player + '_' + field, 'blk_possibleBowl');

                        // check if entry is empty, so no direction is set
                        if (possibleMoves[field][0] == 0) {
                            // remember empty direction
                            this.clientStateArgs.direction = 0;
                        } else {
                            // clean possibly set empty direction
                            delete this.clientStateArgs.direction;

                            // check if entry contains captured field
                            for (var capturefield of possibleMoves[field]) {
                                if (typeof capturefield === 'string') {
                                    // capturefield has format '<playerid>_<field>'
                                    // mark opponent's bowl
                                    dojo.addClass('circle_' + capturefield, 'blk_capturedBowl');
                                }
                            }
                        }
                    }

                    // highlight all stones in possible bowls
                    dojo.query('.blk_possibleBowl').query('.blk_stone').addClass('blk_possibleStone');
                }
            },

            // show selectable directions after selecting bowl
            updateMoveDirection: function (possibleMoves) {
                console.log("Enter updateMoveDirection");

                // only display for current player
                if (this.isCurrentPlayerActive()) {
                    var player = this.getActivePlayerId();
                    // Remove previously set css markers for possible bowls, stones and directions
                    dojo.query('.blk_possibleBowl').removeClass('blk_possibleBowl');
                    dojo.query('.blk_possibleStone').removeClass('blk_possibleStone');
                    dojo.query('.blk_possibleDirection').removeClass('blk_possibleDirection');

                    // change selected bowl for cancelling
                    dojo.addClass('circle_' + player + '_' + this.clientStateArgs.field, 'blk_selectedBowl');
                    // data for active player in array, check for non-capture field
                    for (var field of possibleMoves[this.clientStateArgs.field]) {
                        if (typeof field !== 'string') {
                            // non-capture field has format <field>
                            // selectable directions
                            dojo.addClass('circle_' + player + '_' + field, 'blk_possibleDirection');
                        }
                    }

                    // highlight all stones in possible directions and selected bowl
                    dojo.query('.blk_possibleDirection').query('.blk_stone').addClass('blk_possibleStone');
                    dojo.query('.blk_selectedBowl').query('.blk_stone').addClass('blk_selectedStone');
                }
            },

            // show all bowls including seed area as editable
            updateEditBowls: function () {
                console.log("Enter updateEditBowls");

                // only display for current player
                if (this.isCurrentPlayerActive()) {
                    // highlight all bowls and possibly seed areas
                    dojo.query('.blk_circle').addClass('blk_editBowl');
                    dojo.query('.blk_seed_area').addClass('blk_editBowl');
                    dojo.query('.blk_circle').removeClass('blk_clearBowl');
                    dojo.query('.blk_seed_area').removeClass('blk_clearBowl');
                }
            },

            // show all bowls including seed area as clearable
            updateClearBowls: function () {
                console.log("Enter updateClearBowls");

                // only display for current player
                if (this.isCurrentPlayerActive()) {
                    // highlight all bowls and possibly seed areas
                    dojo.query('.blk_circle').removeClass('blk_editBowl');
                    dojo.query('.blk_seed_area').removeClass('blk_editBowl');
                    dojo.query('.blk_circle').addClass('blk_clearBowl');
                    dojo.query('.blk_seed_area').addClass('blk_clearBowl');
                }
            },

            // taken from https://boardgamearena.com/doc/Game_options_and_preferences:_gameoptions.inc.php
            setupPreference: function () {
                // Extract the ID and value from the UI control
                var _this = this;
                function onchange(e) {
                    var match = e.target.id.match(/^preference_[cf]ontrol_(\d+)$/);
                    if (!match) {
                        return;
                    }
                    var prefId = +match[1];
                    var prefValue = +e.target.value;
                    _this.prefs[prefId].value = prefValue;
                    _this.onPreferenceChange(prefId, prefValue);
                }
                
                // Call onPreferenceChange() when any value changes
                dojo.query(".preference_control").connect("onchange", onchange);
                
                // Call onPreferenceChange() now
                dojo.forEach(
                    dojo.query("#ingame_menu_content .preference_control"),
                    function (el) {
                        onchange({ target: el });
                    }
                );
              },
            onPreferenceChange: function (prefId, prefValue) {
                // only consider game preferences
                if (prefId >= 100 && prefId <= 199) {
                    console.log("Preference changed", prefId, prefValue);
                    dojo.byId("checkbox_kichwa_mode").checked = (this.prefs[PREF_KICHWA_MODE].value == PREF_KICHWA_MODE_AUTOMATIC) ? true : false;
                }   
            },
            updatePreference: function(prefId, newValue) {
                // Select preference value in control:
                dojo.query('#preference_control_' + prefId + ' > option[value="' + newValue
                // Also select fontrol to fix a BGA framework bug:
                    + '"], #preference_fontrol_' + prefId + ' > option[value="' + newValue
                    + '"]').forEach((value) => dojo.attr(value, 'selected', true));
                // Generate change event on control to trigger callbacks:
                const newEvt = document.createEvent('HTMLEvents');
                newEvt.initEvent('change', false, true);
                $('preference_control_' + prefId).dispatchEvent(newEvt);
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

            // player has clicked on a bowl to make an action
            onBowl: function (evt) {
                console.log("Enter onBowl");

                // Check that this action is possible at this moment, don't show message for 1st check
                if (!(this.checkAction('executeMove', true) || this.checkAction('selectKichwa', true) 
                    || this.checkAction('edit'))) {
                    return;
                }

                // Stop event propagation
                dojo.stopEvent(evt);

                var params = evt.currentTarget.id.split('_');
                var player = params[1];
                var field = params[2];
                var player1 = this.clientStateArgs.player1;
                var player2 = this.clientStateArgs.player2;

                // first: check for one of the special editor options
                if (dojo.hasClass('circle_' + player + '_' + field, 'blk_editBowl')) {
                    // translate from gui order to board order
                    var order = player == player2 ? 1 : 0;
                    var index = order * 17 + parseInt(field);
                    // do not allow more than 20 stones to prevent gui overload, just do nothing then
                    if (this.clientStateArgs.board[index].count < 20) {
                        if (field == 0) {
                            // for storage area a stone in both has to be placed to ensure equality
                            // note: don't care about stone number, will be refreshed before game start
                            this.addStoneOnBoard(player1, 0, 1);    
                            this.addStoneOnBoard(player2, 0, 1); 
                            this.clientStateArgs.board[0].count++;   
                            this.clientStateArgs.board[17].count++;   

                            // update labels to display stone count
                            dojo.byId('label_' + player1 + '_0').innerHTML = "<p>" + this.clientStateArgs.board[0].count + "</p>";
                            dojo.byId('label_' + player2 + '_0').innerHTML = "<p>" + this.clientStateArgs.board[17].count + "</p>";
                        } else {
                            // for others calculate index since player order and fields are always the same
                            this.addStoneOnBoard(player, field, 1);
                            this.clientStateArgs.board[index].count++

                            // update label to display stone count
                            dojo.byId('label_' + player + '_' + field).innerHTML = "<p>" + this.clientStateArgs.board[order * 17 + parseInt(field)].count + "</p>";
                        }
                    }

                    return;
                }
                if (dojo.hasClass('circle_' + player + '_' + field, 'blk_clearBowl')) {
                    if (field == 0) {
                        // for storage area both have to be emptied to ensure equality
                        // note: don't care about deleted stone numbers, will be refreshed before game start
                        dojo.query('#circle_' + player1 + '_' + field + ' > .blk_stone').forEach(dojo.destroy);
                        dojo.query('#circle_' + player2 + '_' + field + ' > .blk_stone').forEach(dojo.destroy);
                        this.clientStateArgs.board[0].count = 0;   
                        this.clientStateArgs.board[17].count = 0;   

                        // update labels to display stone count
                        dojo.byId('label_' + player1 + '_0').innerHTML = "<p>0</p>";
                        dojo.byId('label_' + player2 + '_0').innerHTML = "<p>0</p>";
                    } else {
                        // for others calculate index since player order and fields are always the same
                        dojo.query('#circle_' + player + '_' + field + ' > .blk_stone').forEach(dojo.destroy);
                        var order = player == player2 ? 1 : 0;
                        this.clientStateArgs.board[order * 17 + parseInt(field)].count = 0;

                        // update label to display stone count
                        dojo.byId('label_' + player + '_' + field).innerHTML = "<p>0</p>";
                    }

                    return;
                }

                // action #1: check if a possible bowl has been clicked
                if (dojo.hasClass('circle_' + player + '_' + field, 'blk_possibleBowl')) {
                    console.log("Possible bowl clicked: " + field);
                    // remember selected field in client
                    this.clientStateArgs.field = field;

                    // check if no direction is set, since this immediately triggers server call without further client state
                    if (this.clientStateArgs.direction == 0) {                    
                        // call server, game mode will be handled there
                        this.ajaxcall("/baolakiswahili/baolakiswahili/executeMove.html", {
                            lock: true,
                            player: player,
                            field: field,
                            direction: 0
                        }, this, function (result) { });
                    // check if kichwa was selected, then direction is obvious and selection can be skipped
                    // note: this is always done automatically since it will annoy the user, the automatic kichwa selection is configurable, 
                    // since this is a new move and even if he only has one option, the player might want to check the board before doing it
                    } else if (this.clientStateArgs.type == 'kichwa') {
                        // determine direction which has to be selected and call server, game mode will be handled there
                        var direction = field == 1 ? 2 : 7;
                        this.ajaxcall("/baolakiswahili/baolakiswahili/executeMove.html", {
                            lock: true,
                            player: player,
                            field: field,
                            direction: direction
                        }, this, function (result) { });
                    } else {
                        // set new client state (different for variants)
                        if (this.clientStateArgs.variant == VARIANT_HUS) {
                            this.setClientState('client_directionSelection', {
                                descriptionmyturn: _('${you} must select a direction'),
                            });
                        } else {
                            this.setClientState('client_directionSelection', {
                                descriptionmyturn: _('${you} must select direction for ${type_translated} move'),
                            });
                        }
                    }

                    return;
                }

                // action #2: check if a selected bowl has been clicked
                if (dojo.hasClass('circle_' + player + '_' + field, 'blk_selectedBowl')) {
                    console.log("Selected bowl clicked: " + field);

                    // same handling as on cancel button
                    this.onCancel(evt);
    
                    return;
                }
                // action #3: check if a direction has been clicked
                if (dojo.hasClass('circle_' + player + '_' + field, 'blk_possibleDirection')) {
                    console.log("Direction clicked: " + field);

                    // call server, game mode will be handled there
                    this.ajaxcall("/baolakiswahili/baolakiswahili/executeMove.html", {
                        lock: true,
                        player: player,
                        field: this.clientStateArgs.field,
                        direction: field
                    }, this, function (result) { });

                    return;
                }
            },

            // click on Cancel button       
            onCancel: function (evt) {
			    console.log("Enter onCancel");

                // Check that this action is possible at this moment, don't show message for 1st check
                if (!(this.checkAction('executeMove', true) || this.checkAction('selectKichwa'))) {
                    return;
                }

                // Stop event propagation
                dojo.stopEvent(evt);

                // force re-init
                this.restoreServerGameState();
            },

            // click on Go on safari button       
            onGoSafari: function (evt) {
			    console.log("Enter onGoSafari");

                // Check that this action is possible at this moment
                if (!this.checkAction('decideSafari')) {
                    return;
                }

                // Stop event propagation
                dojo.stopEvent(evt);

                // call server, game mode will be handled there,
                // field and direction will have no meaning for the move itself, since server will use correct values for nyumba and previous direction,
                // field = 0 is used for telling the server about the safari decision: direction = 1 means safari, direction = 0 means stop
                var player = this.getActivePlayerId();
                this.ajaxcall("/baolakiswahili/baolakiswahili/executeMove.html", {
                    lock: true,
                    player: player,
                    field: 0,
                    direction: 1
                }, this, function (result) { });
            },

            // click on Stop move button       
            onStopMove: function (evt) {
			    console.log("Enter onStopMove");

                // Check that this action is possible at this moment
                if (!this.checkAction('decideSafari')) {
                    return;
                }

                // Stop event propagation
                dojo.stopEvent(evt);

                // remove marked nyumba
                dojo.query('.blk_capturedBowl').removeClass('blk_capturedBowl');

                // call server, game mode will be handled there,
                // field and direction will have no meaning for the move itself, since server will use correct values for nyumba and previous direction,
                // field = 0 is used for telling the server about the safari decision: direction = 1 means safari, direction = 0 means stop
                var player = this.getActivePlayerId();
                this.ajaxcall("/baolakiswahili/baolakiswahili/executeMove.html", {
                    lock: true,
                    player: player,
                    field: 0,
                    direction: 0
                }, this, function (result) { });
            },

            // click on swith player button in editor
            onSwitchEditPlayer: function (evt) {
			    console.log("Enter onSwitchPlayer");

                // Check that this action is possible at this moment
                if (!this.checkAction('edit')) {
                    return;
                }

                // Stop event propagation
                dojo.stopEvent(evt);

                // restore all bowls and possibly seed areas
                dojo.query('.blk_circle').removeClass('blk_editBowl');
                dojo.query('.blk_seed_area').removeClass('blk_editBowl');
                dojo.query('.blk_circle').removeClass('blk_clearBowl');
                dojo.query('.blk_seed_area').removeClass('blk_clearBowl');
                      
                // call server to start selected game mode
                this.ajaxcall("/baolakiswahili/baolakiswahili/switchEditPlayer.html", {
                    lock: true,
                    board: JSON.stringify(this.clientStateArgs.board)
                }, this, function (result) { });
            },

            // click on toggle to clear in editor
            onClearEditing: function (evt) {
			    console.log("Enter onClearEditing");

                // Check that this action is possible at this moment
                if (!this.checkAction('edit')) {
                    return;
                }

                // Stop event propagation
                dojo.stopEvent(evt);

                this.setClientState('client_gameEditClear');
            },

            // click on toggle to edit in editor
            onEditing: function (evt) {
			    console.log("Enter onEditing");

                // Check that this action is possible at this moment
                if (!this.checkAction('edit')) {
                    return;
                }

                // Stop event propagation
                dojo.stopEvent(evt);

                 // force re-init
                 this.restoreServerGameState();
            },

            // click on start game and close editor
            onFinishEditing: function (evt) {
			    console.log("Enter onFinishEditing");

                // Check that this action is possible at this moment
                if (!this.checkAction('edit')) {
                    return;
                }

                // Stop event propagation
                dojo.stopEvent(evt);

                // restore all bowls and possibly seed areas
                dojo.query('.blk_circle').removeClass('blk_editBowl');
                dojo.query('.blk_seed_area').removeClass('blk_editBowl');
                dojo.query('.blk_circle').removeClass('blk_clearBowl');
                dojo.query('.blk_seed_area').removeClass('blk_clearBowl');
                
                // call server to start selected game mode
                this.ajaxcall("/baolakiswahili/baolakiswahili/startWithEditedBoard.html", {
                    lock: true,
                    board: JSON.stringify(this.clientStateArgs.board)
                }, this, function (result) { });
            },

            // player has selected a preference from the preference box under the board
            onPrefCheckbox: function (evt) {
                // get selected preference
                 var prefId = evt.currentTarget.id;
                 var value = document.getElementById(prefId).checked;
                 console.log("Preference " + prefId + " set to " + value);

                 // update user preference
                 if (prefId == "checkbox_kichwa_mode") {
                     this.updatePreference(PREF_KICHWA_MODE, value ? PREF_KICHWA_MODE_AUTOMATIC : PREF_KICHWA_MODE_MANUAL);
                 }
            },

            // click on Testmode button       
            onTestmode: function (evt) {
			    console.log("Enter onTestmode");

                // Stop event propagation
                dojo.stopEvent(evt);

                // call server, activate testmode action
                this.ajaxcall("/baolakiswahili/baolakiswahili/testmode.html", {lock: true}, this, function (result) { });

                // refresh view (F5)
                location.reload();
            },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:
                
                In this method, you associate each of your game notifications with your local method to handle it.
                
                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your baolakiswahili.game.php file.
            
            */

            setupNotifications: function () {
                console.log('notifications subscriptions setup');

                dojo.subscribe('moveStones', this, "notif_moveStones");
                this.notifqueue.setSynchronous('moveStones');
                dojo.subscribe('newScores', this, "notif_newScores");
                dojo.subscribe('placeStones', this, "notif_placeStones");
            },

            /*
            Notification when stones should move.
            */
            notif_moveStones: function (notif) {
                console.log('enter notif_moveStones');
                
                // Remove previously set css markers for possible and captured bowls, stones and directions
                dojo.query('.blk_possibleBowl').removeClass('blk_possibleBowl');
                dojo.query('.blk_possibleDirection').removeClass('blk_possibleDirection');
                dojo.query('.blk_selectedBowl').removeClass('blk_selectedBowl');
                dojo.query('.blk_possibleStone').removeClass('blk_possibleStone');
                dojo.query('.blk_selectedStone').removeClass('blk_selectedStone');
                dojo.query('.blk_capturedBowl').removeClass('blk_capturedBowl');

                // get players
                var player = notif.args.player;
                var opponent = notif.args.opponent;
                var players = [player, opponent];

                // get all stones in all circles and their stones to have them ready for the moves
                // and avoid problems during animations and reattachement of html elements
                var circles = new Map();
                for (i = 0; i <= 16; i++) {
                    for (p of players) {
                        var id = 'circle_' + p + '_' + i;
                        var circle = dojo.byId(id);
                        var nodes = dojo.query('#' + id + ' > .blk_stone');
                        var stones = [];
                        for (j = 0; j < nodes.length; j++) {
                            stones.push(nodes[j].id);
                        }
                        circles.set(id, stones);
                    }
                }

                // list for animations for each move
                var animations = [];
                // list of stones to move
                var movingStones = [];

                // regular timings per animation and at end of animation
                var animDelay = 333;
                var endDelay = 500;
                // if in fast mode in game archive replay switch off 
                if (this.instantaneousMode) {
                    animDelay = 0;
                    endDelay = 0;
                }

                for (var move in notif.args.moves) {
                    // extract command and field of a move
                    var params = notif.args.moves[move].split('_');
                    var command = params[0];
                    var field = params[1];

                    switch (command) {
                        case "emptyActive":
                            // put all stones (= their ids) into list and empty their circle
                            var stones = circles.get('circle_' + player + '_' + field);
                            for (var id = 0; id < stones.length; id++) {
                                movingStones.push(stones[id]);
                            }
                            circles.set('circle_' + player + '_' + field, []);
                            break;
                        case "emptyopponent":
                            // put all oposite stones (= their ids) into list and empty their circle
                            var stones = circles.get('circle_' + opponent + '_' + field);
                            for (var id = 0; id < stones.length; id++) {
                                movingStones.push(stones[id]);
                            }
                            circles.set('circle_' + opponent + '_' + field, []);
                            break;
                        case "moveActive":
                            // move all remaining stones to new field
                            var combinedAnimation = [];
                            for (var id = 0; id < movingStones.length; id++) {
                                var stone = movingStones[id];
                                // change constructed animation to have positional offset
                                var currentAnimation = this.slideToObject(stone, 'circle_' + player + '_' + field, animDelay);
                                currentAnimation.properties.left += Math.floor((Math.random() * 11) - 5) * 2;
                                currentAnimation.properties.top += Math.floor((Math.random() * 11) - 5) * 2;
                                combinedAnimation.push(currentAnimation);
                            }
                            // combine all animations to one
                            animations.push(dojo.fx.combine(combinedAnimation));

                            // leave one stone in current field
                            var stone = movingStones.splice(0, 1)[0];
                            var stones = circles.get('circle_' + player + '_' + field);
                            stones.push(stone);
                            break;
                        case "moveopponent":
                            // move all emptied stones of captured field to own field (adjacent or selected kichwa)
                            var combinedAnimation = [];
                            for (var id = 0; id < movingStones.length; id++) {
                                var stone = movingStones[id];
                                // change constructed animation to have positional offset
                                var currentAnimation = this.slideToObject(stone, 'circle_' + player + '_' + field, animDelay);
                                currentAnimation.properties.left += Math.floor((Math.random() * 11) - 5) * 2;
                                currentAnimation.properties.top += Math.floor((Math.random() * 11) - 5) * 2;
                                combinedAnimation.push(currentAnimation);
                            }
                            // combine all animations to one
                            animations.push(dojo.fx.combine(combinedAnimation));

                            // leave one stone in current field
                            var stone = movingStones.splice(0, 1)[0];
                            var stones = circles.get('circle_' + player + '_' + field);
                            stones.push(stone);
                            break;
                        case "placeActive":
                            var id = 'circle_' + player + '_0';
                            var nodes = dojo.query('#' + id + ' > .blk_stone');
                            var stone = nodes[0].id;
                            // change constructed animation to have positional offset
                            var currentAnimation = this.slideToObject(stone, 'circle_' + player + '_' + field, animDelay);
                            currentAnimation.properties.left += Math.floor((Math.random() * 11) - 5) * 2;
                            currentAnimation.properties.top += Math.floor((Math.random() * 11) - 5) * 2;
                            animations.push(currentAnimation);
                            // put stone in new field
                            var stones = circles.get('circle_' + player + '_' + field);
                            stones.push(stone);
                            break;
                        case "taxActive":
                            // put 2 stones (= their ids) into list and remove from circle
                            var stones = circles.get('circle_' + player + '_' + field);
                            movingStones.push(stones[0]);
                            movingStones.push(stones[1]);
                            stones.splice(0, 2);
                            circles.set('circle_' + player + '_' + field, stones);
                            break;
                        }
                }

                // chain all animations to one in order 
                var anim = dojo.fx.chain(animations);
                // will be called after all animations are done
                dojo.connect(anim, "onEnd", () => {
                    // attach all stones to correct circles after move is done
                    for ([circle, stones] of circles.entries()) {
                        for (var id = 0; id < stones.length; id++) {
                            // "this" points to the outer function due to () => call
                            this.attachToNewParent(stones[id], circle);
                        }
                    }

                    // update bowl labels
                    board = notif.args.board;
                    for (var player in board) {
                        for (var field in board[player]) {
                            var bowl = board[player][field];

                            // update label to display stone count
                            dojo.byId('label_' + bowl.player + '_' + bowl.no).innerHTML = "<p>" + bowl.count + "</p>";
                        }
                    }
                });
                // execute animation
                anim.play();
                // synchronize duration so that game waits until finished 
                // add a bit of time to let onEnd callback function be executed before continuing
                this.notifqueue.setSynchronousDuration(anim.duration + endDelay);

                console.log('leaving notif_moveStones');
            },

            /*
            Notification when score changes.
            */
            notif_newScores: function (notif) {
                console.log('enter notif_newScores');

                // update scores and nyumba text (onyly used in KISWAHILI variant)
                for (var player_id in notif.args.scores) {
                    var newScore = notif.args.scores[player_id];
                    this.scoreCtrl[player_id].toValue(newScore);

                    // assume destroyed nyumba, set otherwise if so
                    var message = _('Nyumba is destroyed');
                    if (notif.args['nyumba_' + player_id] == 0) {
                        message = _('Nyumba is functional');
                    } else if (notif.args['nyumba_' + player_id] == 1) {
                        message = _('Nyumba is not functional');
                    }
                    dojo.byId('nyumba_message_'+player_id).innerHTML = message;
                }

                // update gamelog
                dojo.byId('gamelog_content').innerHTML = notif.args.gamelog;
        },

            /*
            Notification when stones should be placed for other players while editing.
            Note: This gets done by simply refreshing to keep it simple and since not being in real game yet. Besides, this orders the stone numbers correctly.
            */
            notif_placeStones: function (notif) {
                console.log('enter notif_placeStones');

                // empty board
                dojo.query('.blk_stone').forEach(dojo.destroy);

                // place all stones on board
                var board = notif.args.board;
                this.fillBoard(board);
                this.clientStateArgs.board = board;
            }
        });
    });
