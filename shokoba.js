/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * shokoba implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * shokoba.js
 *
 * shokoba user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.shokoba", ebg.core.gamegui, {
        constructor: function(){
            console.log('shokoba constructor');

            //dev image
            /*
            this.cardwidth = 100;
            this.cardheight = 100;
            this.cards_url = g_gamethemeurl + 'img/cards.png';
            */
            //official image
            this.cardwidth = 250;
            this.cardheight = 250;
            this.cards_url = null;
            //g_gamethemeurl + 'img/cards.png';


            this.styles = null;
            this.current_style_id = null;

            this.cards_per_row = 5;

            this.playersHand = [];
            this.playerHand = null;
            this.tableCard = null;
            this.deck = null;

            this.visual=1;
        },


        onChangeForCardStyle : function(event) {

            var select = event.currentTarget;
            var current_style = 'shokoba_' + this.current_style_id;
            var new_style_id = select.options[select.selectedIndex].value ;
            var new_style = 'shokoba_' + new_style_id;

            // Set that new style as the player preference
            dojo.query('#preference_control_100 > option[value="' + (new_style_id + 1) + '"], #preference_fontrol_100 > option[value="' + (new_style_id + 1) + '"]').forEach(function(node) {
                dojo.attr(node, 'selected', true);
            });

            // Change style of cards on table
            dojo.query('.' + current_style).addClass(new_style).removeClass(current_style);

            // Set the new style for cards which will appear in the stocks
            var stocks = [this.tableCard,this.playerHand];
            stocks.concat( this.playersHand)

            for (i in stocks) {
                var stock = stocks[i];
                for (j in stock.item_type) {
                    var item = stock.item_type[j];
                    if (j == 0) {
                        var image = item.image.replace(current_style, new_style);
                    }
                    item.image = image;
                }
                stock.updateDisplay();
            }

            // Change style of the current visible cards in the stocks
            image = 'url(' + image + ')';
            dojo.query('.stockitem').forEach(function(node) {
                dojo.style(node, 'background-image', image);
            });

            // Change the name of the deck used
            //$('current_style').innerHTML = _(this.styles[new_style_id]);

            this.current_style_id = new_style_id;

        },

        ChangeVisual : function(){

            var nodeList=document.querySelectorAll(".player")

            if (this.visual==2){
                 document.getElementById("myhand_wrap").style.display = "none";
                for (var index = 0; index < nodeList.length; index++) {
                    nodeList[index].style.display = "block";
                }

                for (i in this.playersHand) {
                    var stock = this.playersHand[i];
                    stock.updateDisplay();
                }
            }else{
                 document.getElementById("myhand_wrap").style.display = "block";
                for (var index = 0; index < nodeList.length; index++) {
                    nodeList[index].style.display = "none";
                }
                this.playerHand.updateDisplay();
            }

        },

        onChangeVisual : function(event) {

            var select = event.currentTarget;
            this.visual = select.options[select.selectedIndex].value ;

            this.ChangeVisual();
        },

        /*
        * Create players card
        */
        createPlayerStock: function (data) {
            this.cardMap = {};
            this.playerPile = [];
            var count = 0;
            for (var player_id in data.players) count++; // IE8 compatibility... sad :'(
            var p =1
            for (var player_id in data.players) {
                var stock_player = new ebg.stock();
                document.getElementById('hand_' + p).setAttribute("id", 'hand_' + player_id);
                p=p+1

                stock_player.create(this, $('hand_' + player_id), this.cardwidth, this.cardheight);

                stock_player.setSelectionAppearance('class');
                //active player -> 1

                stock_player.item_margin=10;
                stock_player.image_items_per_row = 10;

                this.playerPile[player_id] = stock_player;
                if (Number(player_id) === this.playerId) {
                    stock_player.setSelectionMode(1);
                    dojo.connect(stock_player, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');

                } else {
                    stock_player.setSelectionMode(0);
                }

                var position_in_sprite = 0;
                for(var color=1;color<=4;color++) {
                    for(var value=1;value<=10;value++) {
                        // Build card type id
                        var card_id = this.getCardUniqueId(color, value);
                        stock_player.addItemType(card_id, null, this.cards_url, card_id);
                        position_in_sprite++;
                    }
                }

                // Cards in player's hand
                for(var i in this.gamedatas['hand'+ player_id]) {
                    var card = this.gamedatas['hand'+ player_id][i];
                    var value = card.type_arg;
                    var color = card.type;

                    stock_player.addToStockWithId(this.getCardUniqueId(color, value), card.id);
                }
                this.playersHand[player_id] = stock_player;

            }
        },

        /*
         * Card styles management
         */
        getAvailableStyles: function() {
            var available_styles = [];
            dojo.query('#preference_control_100 > option').forEach(function(node) {
                available_styles.push(node.innerHTML)
            });
            return available_styles;
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

            this.playerId = Number(gamedatas.player_id);
            this.playerCard = { drag: 'none', selectedItemId: null, nodes: [] };
            this.tableCard = { drag: 'none', selectedItemId: null, nodes: [] };

            switch(gamedatas['nbplayer']) {
                case 4:
                    document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                        <div class="whiteblock player" id="player1_4" class="whiteblock">
                        <div class="hand" id="hand_1"></div>
                        </div>
                        <div class="whiteblock player" id="player2_4" class="whiteblock">
                        <div class="hand" id="hand_2"></div>
                        </div>
                        <div class="table">
                        <div class="deck" id="deck"></div>
                        <div id="player-tables"></div>
                        </div>
                        <div class="whiteblock player" id="player3_4" class="whiteblock">
                        <div class="hand" id="hand_3"></div>
                        </div>
                        <div class="whiteblock player" id="player4_4" class="whiteblock">
                        <div class="hand" id="hand_4"></div>
                        </div>
                        <div id="myhand_wrap" class="whiteblock">
                        <div class="hand" id="hand"></div>
                        </div>
                    `);
                     break;
                case 3:
                    document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                        <div class="whiteblock player" id="player1_3" class="whiteblock">
                        <div class="hand" id="hand_1"></div>
                        </div>
                        <div class="whiteblock player" id="player2_3" class="whiteblock">
                        <div class="hand" id="hand_2"></div>
                        </div>
                        <div class="table">
                        <div class="deck" id="deck"></div>
                        <div id="player-tables"></div>
                        </div>
                        <div class="whiteblock player" id="player3_3" class="whiteblock">
                        <div class="hand" id="hand_3"></div>
                        </div>
                        <div id="myhand_wrap" class="whiteblock">
                        <div class="hand" id="hand"></div>
                        </div>
                    `);
                     break;
                case 2:
                    document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                        <div class="whiteblock player" id="player1_2" class="whiteblock">
                        <div class="hand" id="hand_1"></div>
                        </div>
                        <div class="table">
                        <div class="deck" id="deck"></div>
                        <div id="player-tables"></div>
                        </div>
                        <div class="whiteblock player" id="player2_2" class="whiteblock">
                        <div class="hand" id="hand_2"></div>
                        </div>
                        <div id="myhand_wrap" class="whiteblock">
                        <div class="hand" id="hand"></div>
                        </div>
                    `);
                     break;
            }

            var stock_table = new ebg.stock();
            stock_table.create(this, $('player-tables'), this.cardwidth, this.cardheight);
            stock_table.setSelectionMode(2);
            stock_table.setSelectionAppearance('class');

            this.styles = this.getAvailableStyles();

            this.current_style_id = this.prefs[100].value;

            stock_table.item_margin=10;
            stock_table.centerItems = true;

            this.cards_url = g_gamethemeurl + 'img/shokoba_'+this.current_style_id+'.png';

            stock_table.image_items_per_row = 10;
            dojo.connect(stock_table, 'onChangeSelection', this, 'onTableSelectionChanged');
            dojo.connect($('preference_control_100'), 'onchange', this, 'onChangeForCardStyle');
            dojo.connect($('preference_fontrol_100'), 'onchange', this, 'onChangeForCardStyle');

            dojo.connect($('preference_fontrol_101'), 'onchange', this, 'onChangeVisual');

            var stock_deck = new ebg.stock();
            stock_deck.create(this, $('deck'), this.cardwidth, this.cardheight);
            stock_deck.setSelectionMode(0);
            stock_deck.setSelectionAppearance('class');
            stock_deck.item_margin=0;
            stock_deck.image_items_per_row = 10;
            stock_deck.autowidth = true;
            stock_deck.setOverlap(1, 0);

            for(var color=1;color<=4;color++) {
                for(var value=1;value<=10;value++) {
                    // Build card type id
                    var card_id = this.getCardUniqueId(color, value);
                    stock_table.addItemType(card_id, null, this.cards_url, card_id);
                    stock_deck.addItemType(card_id, null, this.cards_url, card_id);
                }
            }

            for ( var i in this.gamedatas['table' ]) {
                var card = this.gamedatas['table'][i];
                var value = card.type_arg;
                var color=card.type;
                stock_table.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }

            this.tableCard = stock_table;

            for ( var i in this.gamedatas['deck' ]) {
                var card  = this.gamedatas['deck'][i];
                var value = card.type_arg;
                var color = card.type;

                stock_deck.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }

            this.deck = stock_deck;

            this.createPlayerStock(gamedatas);

            var stock_player = new ebg.stock();
            stock_player.create(this, $('hand'), this.cardwidth, this.cardheight);

            stock_player.setSelectionMode(1);

            // check diff
            stock_player.setSelectionAppearance('class');
            stock_player.item_margin=10;
            stock_player.centerItems = true;

            stock_player.image_items_per_row = 10;

            //stock.onItemCreate = dojo.hitch(this, 'onCreateNewCard');

            dojo.connect(stock_player, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');

            var position_in_sprite = 0;
            for(var color=1;color<=4;color++) {
                for(var value=1;value<=10;value++) {
                    // Build card type id
                    var card_id = this.getCardUniqueId(color, value);
                    stock_player.addItemType(card_id, null, this.cards_url, card_id);
                    position_in_sprite++;
                }
            }

            // Cards in player's hand
            for(var i in this.gamedatas['hand']) {
                var card  = this.gamedatas['hand'][i];
                var value = card.type_arg;
                var color = card.type;
                stock_player.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }
            this.playerHand = stock_player;

            this.visual=this.prefs[101].value;

            this.ChangeVisual();

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },


        getCardUniqueId : function(color, value) {
            return (color - 1) * 10 + (value-1) ;
        },


        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */

        ///////////////////////////////////////////////////
        //// Player's action


        setChooseActionState: function () {
            this.statusBar.removeActionButtons()
            this.changeMainBar(_('You must play a card or pass'));
//            this.unhiglightCards();
            this.SelectionType = 'hand';
            this.playerHand.unselectAll();
            this.tableCard.unselectAll();
        },

        onCardClick: function( card_id ){
            console.log( 'onCardClick', card_id );

            this.bgaPerformAction("actPlayCard", {
                card_id,
            }).then(() =>  {
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });
        },

        onTakeCard: function () {
            var playerCard_id;
            var tableCard_ids = [];
            for (let i=0; i < this.tableCard.getSelectedItems().length; i++) {
                tableCard_ids.push ( this.tableCard.getSelectedItems()[i].id);
            };

            if ( this.playerHand.getSelectedItems().length == 1 ){
                playerCard_id = this.playerHand.getSelectedItems()[0].id;
            }else if ( this.playerPile[this.playerId].getSelectedItems().length == 1 ){
                playerCard_id = this.playerPile[this.playerId].getSelectedItems()[0].id;
            }else{
                playerCard_id = -1;
            }

            this.bgaPerformAction('actTakeCard', {
                tableCard_ids: tableCard_ids.join(','),
                playerCard_id: playerCard_id
            });
        },

        onLeaveCard: function () {
            this.bgaPerformAction('actLeaveCard', {
                card_id: this.playerCard.selectedItemId
            });
        },

        ///////////////////////////////////////////////////
        //// Interface action


        changeMainBar: function (message) {
            $('generalactions').innerHTML = '';
            $('pagemaintitletext').innerHTML = message;
        },

        setPlayCardState: function () {
            this.changeMainBar(_('You must play a card or pass'));
            if (this.visual==2){
                var playerHand = this.playerPile[this.playerId];
            }else{
                var playerHand = this.playerHand;
            }
            var tableCard = this.tableCard;

            if ((tableCard.getSelectedItems().length > 0) && (playerHand.getSelectedItems().length > 0)) {
                this.statusBar.addActionButton(_('Take selected card'),() => this.onTakeCard());
            }else{
                this.statusBar.addActionButton(_('Take selected card'),() => this.onTakeCard(),{classes: 'disabled'});
            }

            if (playerHand.getSelectedItems().length == 0){
                this.statusBar.addActionButton(_('Leave selected card'),() => this.onLeaveCard(),{classes: 'disabled '});
            }else{
                this.statusBar.addActionButton(_('Leave selected card'),() => this.onLeaveCard());
            }

            this.statusBar.addActionButton(_('Cancel'),() => this.setChooseActionState())
       },

        onPlayerHandSelectionChanged: function () {

            if (this.visual==2){
                var playerHand = this.playerPile[this.playerId];
            }else{
                var playerHand = this.playerHand;
            }

            if (playerHand.getSelectedItems().length == 0) {
                this.setPlayCardState();
                return;
            }

            var items = playerHand.getSelectedItems();
            if (items.length > 0) {
                this.SelectionType = 'hand';
                this.setPlayCardState();
                this.playerCard.selectedItemId = items[0].id;
            } else if (this.SelectionType === 'hand') {
                this.setChooseActionState();
            }

        },

        onTableSelectionChanged: function () {

            var tableCard = this.tableCard;

            if (tableCard.getSelectedItems().length == 0) {
                this.setPlayCardState();
                return;
            }

                var items = tableCard.getSelectedItems();
                if (items.length > 0) {
                    this.SelectionType = 'table';
                    this.setPlayCardState();
                    this.tableCard.selectedItemId = items[0].id;
                } else if (this.SelectionType === 'table') {
                    this.setChooseActionState();
                }

        },

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            In this method, you associate each of your game notifications with your local method to handle it.
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your shokoba.game.php file.
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            this.bgaSetupPromiseNotifications();
        },


        /*
            notif_newHandPrivate:
            Refresh Player card
        */

        notif_newHandPrivate: function(args) {
            for (var i in args.hand) {
                var card = args.hand[i];
                var color = card.type;
                var value = card.type_arg;

                this.playerPile[this.playerId ].addToStockWithId(this.getCardUniqueId(color, value), card.id);
                this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
                this.deck.removeFromStockById(card.id);
            }
        },

        /*
            notif_newHand:
            Refresh all other player card
        */


        notif_newHand: function(args) {
            for (var i in args.hand) {
                var card = args.hand[i];
                var color = card.type;
                var value = card.type_arg;

                if(this.playerId != args.player_id){
                    this.playerPile[args.player_id].addToStockWithId(this.getCardUniqueId(color, value), card.id);
                    this.deck.removeFromStockById(card.id);
                }

            }
        },

        /*
            notif_newTable:
            Refresh table card
        */

        notif_newTable: function(args) {

            this.tableCard.removeAll();

            for (var i in args.table) {
                var card = args.table[i];
                var color = card.type;
                var value = card.type_arg;
                this.tableCard.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }

            for (var i in args.deck) {
                var card = args.deck[i];
                var color = card.type;
                var value = card.type_arg;
                this.deck.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }

        },

        /*
            notif_leaveCard:
            move card from player to the table (when player decide to leave a card)
        */

        notif_leaveCard: function (args) {
            var player_id = args.player_id;
            var card_id = args.card_id;
            var card_type = args.card_type
            if (this.playerId == player_id) {
                this.playerHand.removeFromStockById(card_id);
            }

            for (var player in this.playerPile){
                this.playerPile[player].removeFromStockById(card_id)
            };

            this.tableCard.addToStockWithId(card_type,card_id);
        },

        /*
            notif_newScores:
            refresh score
        */

        notif_newScores: async function( args ){
            for( var player_id in args.scores ){
                var newScore = args.scores[ player_id ];
                this.scoreCtrl[ player_id ].toValue( newScore );
            }
        },

        /*
            notif_takeCard:
            remove card(s) from table and player hand when the player succefully
            take them
        */

        notif_takeCard: function (args) {
            var player_id = args.player_id;
            var card_id = args.playerCard_id;

            // You played a card. If it exists in your hand, move card from there and remove the corresponding item
            if (this.playerId == player_id) {
                if($('hand_item_' + card_id)) {
                    var card_type = this.playerHand.getItemById(card_id)['type']
                    this.playerHand.removeFromStockById(card_id);
                }
            }

            for (var player in this.playerPile){
                this.playerPile[player].removeFromStockById(card_id)
            };

            var card_ids = args.tableCard_id;
            for (let index = 0; index < card_ids.length; index++) {
                card_id=card_ids[index];
                // You played a card. If it exists in your hand, move card from there and remove the corresponding item
                if($('player-tables_item_' + card_id)) {
                    var card_type = this.tableCard.getItemById(card_id)['type']
                    //got error with noupdate
                    this.tableCard.removeFromStockById(card_id);
                }
            }
            this.tableCard.updateDisplay()
        },

    });
});
