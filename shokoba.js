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

            this.cardwidth = 100;
            this.cardheight = 100;

            this.cards_per_row = 5;
            this.cards_url = g_gamethemeurl + 'img/cards.png';

            this.playerHand = null;

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
            this.dragStatus = { drag: 'none', selectedItemId: null, nodes: [] };

            document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                <div id="player-tables"></div>
                <div id="debug"></div>
            `);

            var stock = new ebg.stock();
            stock.create(this, $('player-tables'), this.cardwidth, this.cardheight);

            stock.image_items_per_row = 10;
            dojo.connect(stock, 'onChangeSelection', this, 'onTableSelectionChanged');

            for(var color=1;color<=4;color++) {
                for(var value=1;value<=10;value++) {
                    // Build card type id
                    var card_id = this.getCardUniqueId(color, value);
                    stock.addItemType(card_id, null, this.cards_url, card_id);
                }
            }

            for ( var i in this.gamedatas['table' ]) {
                var card = this.gamedatas['table'][i];
                var value = card.type_arg;
                var color=card.type;
                stock.addToStockWithId(this.getCardUniqueId(color, value), card.id);

            }
            this.tablePile = stock;
            // Example to add a div on the game area

            // TODO: Set up your game interface here, according to "gamedatas"

//            this.players = gamedatas.players;

            var player_number = Object.keys(gamedatas.players).length;

            document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                <div id="myhand_wrap" class="whiteblock">
                 <div id="hand"></div>
                <div id="D"></div>
                </div>
            `);

            var stock = new ebg.stock();
            stock.create(this, $('hand'), this.cardwidth, this.cardheight);

            //TO DO should be active player only
            stock.setSelectionMode(1); // By default, no card can be selected. Some states will change that

            // check diff
            stock.setSelectionAppearance('class');

            stock.centerItems = true;

            stock.image_items_per_row = 10;

            //stock.onItemCreate = dojo.hitch(this, 'onCreateNewCard');

            dojo.connect(stock, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');

            var position_in_sprite = 0;
            for(var color=1;color<=4;color++) {
                for(var value=1;value<=10;value++) {
                    // Build card type id
                    var card_id = this.getCardUniqueId(color, value);
                    stock.addItemType(card_id, null, this.cards_url, card_id);
                    position_in_sprite++;
                }
            }
            // Cards in player's hand


            for(var i in this.gamedatas['hand']) {
                var card = this.gamedatas['hand'][i];
                var value = card.type_arg;
                var color=card.type;
                stock.addToStockWithId(this.getCardUniqueId(color, value), card.id);

            }
            this.playerHand = stock;


            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },


        getCardUniqueId : function(color, value) {
            return (color - 1) * 10 + (value-1) ;
        },


        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName, args );

            switch( stateName )
            {

            /* Example:

            case 'myGameState':

                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );

                break;
           */


            case 'dummy':
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


            case 'dummy':
                break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName, args );
/*
            if( this.isCurrentPlayerActive() )
            {
                switch( stateName )
                {
                 case 'playerTurn':
                    const playableCardsIds = args.playableCardsIds; // returned by the argPlayerTurn

                    // Add test action buttons in the action status bar, simulating a card click:
                    playableCardsIds.forEach(
                        cardId => this.addActionButton(`actPlayCard${cardId}-btn`, _('Play card with id ${card_id}').replace('${card_id}', cardId), () => this.onCardClick(cardId))
                    );

                    this.addActionButton('actPass-btn', _('Pass'), () => this.bgaPerformAction("actPass"), null, null, 'gray');
                    break;
                }
            }
            */
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
        this.changeMainBar(_('You must play a card or pass'));
        this.unhiglightCards();

        this.SelectionType = 'null';
        this.playerHand.unselectAll();
      },


        /*

            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */

        // Example:

        onCardClick: function( card_id )
        {
            console.log( 'onCardClick', card_id );

            this.bgaPerformAction("actPlayCard", {
                card_id,
            }).then(() =>  {
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });
        },

        onTakeCard: function () {
            this.bgaPerformAction('actTakeCard', {
                cardId: this.selectedCardId // the "cardId" param match the PHP variable name
            });
        },

        onLeaveCard: function () {
            this.bgaPerformAction('actLeaveCard', {
                card_id: this.dragStatus.selectedItemId
            });
        },

      ///////////////////////////////////////////////////
      //// Interface action

//may have to add for table also
      unhiglightCards: function () {
//        for (var player_id in this.playerHand) {
//          if (Number(player_id) !== this.playerId) {
            var playerHand = this.playerHand;
            var items = playerHand.getAllItems();
            for (var i in items) {
              var card_id = items[i]['id'];
//              dojo.removeClass('playertablecard_item_' + card_id, 'target_element');
            }
//          }
//        }
      },


      changeMainBar: function (message) {
        $('generalactions').innerHTML = '';
        $('pagemaintitletext').innerHTML = message;
      },

      setPlayCardState: function () {
        this.changeMainBar('');
        this.addActionButton('takeCard_button', _('Take selected card'), 'onTakeCard');
        this.addActionButton('leaveCard_button', _('Leave selected card'), 'onLeaveCard');
        this.addActionButton('cancel_button', _('Cancel'), 'setChooseActionState');

        this.unhiglightCards();
      },


      setPlayCardState2: function () {
        this.changeMainBar('');
        this.addActionButton('takeCard_button', _('Take selected card'), 'onTakeCard');
        this.addActionButton('cancel_button', _('Cancel'), 'setChooseActionState');

        this.unhiglightCards();
      },

      onPlayerHandSelectionChanged: function () {

        var playerHand = this.playerHand;

        if (playerHand.getSelectedItems().length == 0) {
          return;
        }

        if (this.checkAction('actTakeCard')) {

            var items = playerHand.getSelectedItems();
            if (items.length > 0) {
                this.SelectionType = 'hand';
                this.setPlayCardState();
                this.dragStatus.selectedItemId = items[0].id;
            } else if (this.SelectionType === 'hand') {
                this.setChooseActionState();
            }
        } else {
            playerHand.unselectAll();
        }
      },

      onTableSelectionChanged: function () {

        var Table = this.tablePile;

        if (Table.getSelectedItems().length == 0) {
          return;
        }

        if (this.checkAction('actTakeCard')) {

          var items = Table.getSelectedItems();
          if (items.length > 0) {
            this.SelectionType = 'table';
            this.setPlayCardState2();
            this.dragStatus.selectedItemId = items[0].id;
          } else if (this.SelectionType === 'table') {
            this.setChooseActionState2();
          }
        } else {
            playerHand.unselectAll();
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

            // TODO: here, associate your game notifications with local methods

            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            //

            dojo.subscribe('leaveCard', this, 'notif_leaveCard');

            dojo.subscribe('newHand', this, 'notif_newHand');

        },


        notif_newHand: function(notif) {

            for (var i in notif.args.hand) {
                var card = notif.args.hand[i];
                var color = card.type;
                var value = card.type_arg;
                this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
            }

        },


      notif_leaveCard: function (notif) {


            var player_id = notif.args.player_id;
            var card_id = notif.args.card_id;

            // You played a card. If it exists in your hand, move card from there and remove the corresponding item
            if($('hand_item_' + card_id)) {

                var card_type = this.playerHand.getItemById(card_id)['type']
                this.playerHand.removeFromStockById(notif.args.card_id);

                this.tablePile.addToStockWithId(card_type,notif.args.card_id);

            }
            this.playerHand[notif.args.player_id]

      },

        // TODO: from this point and below, you can write your game notifications handling methods

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
