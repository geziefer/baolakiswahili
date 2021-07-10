/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * BaoLaKiswahili implementation : © <Alexander Rühl> <alex@geziefer.de>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

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
                var number = 1;
                for (var i in gamedatas.board) {
                    var bowl = gamedatas.board[i];

                    for (var j = 1; j <= bowl.count; j++) {
                        this.addStoneOnBoard(bowl.player, bowl.no, number);
                        number++;
                    }

                    // update label to display stone count
                    dojo.byId('label_' + bowl.player + '_' + bowl.no).innerHTML = "<p>" + bowl.count + "</p>";

                }

                // click handlers for both possible click situations on bowl
                dojo.query('.blk_circle').connect('onclick', this, 'onSelectBowl');
                dojo.query('.blk_circle').connect('onclick', this, 'onSelectDirection');

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);

                switch (stateName) {
                    case 'husMoveSelection':
                        this.updateBowlSelection(args.args.possibleMoves);
                        break;

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

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'husMoveSelection':
                            dojo.query(".basket").addClass('active_slot');
                            dojo.addClass(this.clientStateArgs.token_id, 'selected');
                            this.addActionButton('button_cancel', _('Cancel'), 'onCancel');
                            break;
                        case 'client_husDirectionSelection':
                            dojo.query(".basket").addClass('active_slot');
                            dojo.addClass(this.clientStateArgs.token_id, 'selected');
                            this.addActionButton('button_cancel', _('Cancel'), 'onCancel');
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
            addStoneOnBoard: function (player, field, number) {
                dojo.place(this.format_block('jstpl_stone', {
                    no: number,
                    left: Math.floor(((Math.random() * 5) - 2) * 5) + 20,
                    top: Math.floor(((Math.random() * 5) - 1) * 5) + 25,
                    degree: Math.floor((Math.random() * 73) * 5)
                }), 'circle_' + player + '_' + field);
            },

            updateBowlSelection: function (possibleBowls) {
                // only display for current player
                if (this.isCurrentPlayerActive()) {
                    // Remove previously set css markers for possible bowls, stones and directions
                    dojo.query('.blk_possibleDirection').removeClass('blk_possibleDirection');
                    dojo.query('.blk_selectedBowl').removeClass('blk_selectedBowl');
                    dojo.query('.blk_possibleStone').removeClass('blk_possibleStone');
                    dojo.query('.blk_selectedStone').removeClass('blk_selectedStone');

                    // only 1 player in array
                    for (var player in possibleBowls) {
                        for (var field in possibleBowls[player]) {
                            // every entry in this array is a possible bowl
                            dojo.addClass('circle_' + player + '_' + field, 'blk_possibleBowl');
                        }
                    }

                    // highlight all stones in possible bowls
                    dojo.query(".blk_possibleBowl").query(".blk_stone").addClass("blk_possibleStone");
                }
            },

            updateMoveDirection: function (possibleDirections) {
                // only display for current player
                if (this.isCurrentPlayerActive()) {
                    // Remove previously set css markers for possible bowls, stones and directions
                    dojo.query('.blk_possibleBowl').removeClass('blk_possibleBowl');
                    dojo.query('.blk_possibleStone').removeClass('blk_possibleStone');

                    // only 1 player in array
                    for (var player in possibleDirections) {
                        for (var field in possibleDirections[player]) {
                            // true entries are directions, the false entry is the selected,
                            // if not possible to select, nothing happens
                            if (possibleDirections[player][field] != null) {
                                if (possibleDirections[player][field]) {
                                    dojo.addClass('circle_' + player + '_' + field, 'blk_possibleDirection');
                                }
                                else {
                                    dojo.addClass('circle_' + player + '_' + field, 'blk_selectedBowl');
                                }
                            }
                        }
                    }

                    // highlight all stones in possible directions and selected bowl
                    dojo.query(".blk_possibleDirection").query(".stone").addClass("blk_possibleStone");
                    dojo.query(".blk_selectedBowl").query(".stone").addClass("blk_selectedStone");
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

            onSelectBowl: function (evt) {
                // Stop event propagation
                dojo.stopEvent(evt);

                var params = evt.currentTarget.id.split('_');
                var player = params[1];
                var field = params[2];

                if (!dojo.hasClass('circle_' + player + '_' + field, 'blk_possibleBowl')) {
                    // This is not a possible move => the click does nothing
                    return;
                }

                // remember selected field in client
                this.clientStateArgs.field = field;

                // set new client state
                this.setClientState("client_selectDirection", {
                    descriptionmyturn: _('${you} must select a direction'),
                });
            },

            onSelectDirection: function (evt) {
                // Stop event propagation
                dojo.stopEvent(evt);

                var params = evt.currentTarget.id.split('_');
                var player = params[1];
                var field = params[2];

                if (!dojo.hasClass('circle_' + player + '_' + field, 'blk_possibleDirection')) {
                    if (dojo.hasClass('circle_' + player + '_' + field, 'blk_selectedBowl')) {
                        // Check that this action is possible at this moment
                        if (this.checkAction('selectDirection')) {
                            this.ajaxcall("/baolakiswahili/baolakiswahili/cancelDirection.html", {
                                lock: true,
                                player: player,
                                field: field
                            }, this, function (result) { });
                        }
                    }

                    // This is not a possible move => the click does nothing
                    return;
                }

                // Check that this action is possible at this moment
                if (this.checkAction('selectDirection')) {
                    this.ajaxcall("/baolakiswahili/baolakiswahili/selectDirection.html", {
                        lock: true,
                        player: player,
                        field: field
                    }, this, function (result) { });
                }
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
            },

            /*
            Notification when stones should move.
            */
            notif_moveStones: function (notif) {
                // Remove previously set css markers for possible directions and selected bowl
                dojo.query('.blk_possibleDirection').removeClass('blk_possibleDirection');
                dojo.query('.blk_selectedBowl').removeClass('blk_selectedBowl');
                dojo.query('.blk_possibleStone').removeClass('blk_possibleStone');
                dojo.query('.blk_selectedStone').removeClass('blk_selectedStone');

                // get players
                var player = notif.args.player;
                var oponent = notif.args.oponent;
                var players = [player, oponent];

                // get all stones in all circles and their stones to have them ready for the moves
                // and avoid problems during animations and reattachement of html elements
                var circles = new Map();
                for (i = 1; i <= 16; i++) {
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
                        case "emptyOponent":
                            // put all oposite stones (= their ids) into list and empty their circle
                            var stones = circles.get('circle_' + oponent + '_' + field);
                            for (var id = 0; id < stones.length; id++) {
                                movingStones.push(stones[id]);
                            }
                            circles.set('circle_' + oponent + '_' + field, []);

                            // move all emptied stones to new field
                            var combinedAnimation = [];
                            for (var id = 0; id < stones.length; id++) {
                                var stone = stones[id];
                                combinedAnimation.push(this.slideToObject(stone, 'circle_' + player + '_' + field, 333));
                            }
                            // combine all animations to one
                            animations.push(dojo.fx.combine(combinedAnimation));
                            break;
                        case "moveStone":
                            // move all remaining stones to new field
                            var combinedAnimation = [];
                            for (var id = 0; id < movingStones.length; id++) {
                                var stone = movingStones[id];
                                // change constructed animation to have positional offset
                                var currentAnimation = this.slideToObject(stone, 'circle_' + player + '_' + field, 333);
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
                    }
                }

                // play all animations in order 
                var anim = dojo.fx.chain(animations).play();
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

                // synchronize duration so that game waits until finished 
                this.notifqueue.setSynchronousDuration(anim.duration);

            },

            notif_newScores: function (notif) {
                for (var player_id in notif.args.scores) {
                    var newScore = notif.args.scores[player_id];
                    this.scoreCtrl[player_id].toValue(newScore);
                }
            },
        });
    });
