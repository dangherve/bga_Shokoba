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

            document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                <div id="player-tables"></div>
                <div id="debug"></div>
            `);

            var stock = new ebg.stock();
            stock.create(this, $('player-tables'), this.cardwidth, this.cardheight);

             stock.image_items_per_row = 10;
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

                switch(card.type) {
                    case "saphir":
                        // code block
                        var color=1;
                        break;
                    case "rubis":
                        // code block
                        var color=2;
                        break;
                    case "Emeraudes":
                        // code block
                        var color=3;
                        break;
                    case "Diamond":
                        // code block
                        var color=4;
                        break;
                }
                stock.addToStockWithId(this.getCardUniqueId(color, value), card.id);

            }
            document.getElementById('game_play_area').insertAdjacentHTML('beforeend', text);
            this.tablePile = stock;

            // Example to add a div on the game area


            // TODO: Set up your game interface here, according to "gamedatas"




//            this.players = gamedatas.players;

            var player_number = Object.keys(gamedatas.players).length;
            this.playerPile = [];

             Object.values(gamedatas.players).forEach(player => {
//            for (var player=1; player<=player_number;player++){
                document.getElementById('game_play_area').insertAdjacentHTML('beforeend', `
                            <div id="myhand_wrap-${player.id}" class="whiteblock">
                                <b id="myhand_label">${player.name}</b>
                                <div id="hand-${player.id}"></div>
                                                                <div id="D-${player.id}"></div>
                            </div>

                        `);

                var stock = new ebg.stock();
                stock.create(this, $('hand-'+ player.id), this.cardwidth, this.cardheight);

                //TO DO should be active player only
                stock.setSelectionMode(1); // By default, no card can be selected. Some states will change that

                // check diff
                 stock.setSelectionAppearance('class');

                stock.centerItems = true;

                stock.image_items_per_row = 10;

                //stock.onItemCreate = dojo.hitch(this, 'onCreateNewCard');

                //TO DO should be active player only
                //dojo.connect(stock, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');


                //TO DO should be active player only

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


            for(var i in this.gamedatas['hand' + player.id]) {
                var card = this.gamedatas['hand' + player.id][i];
                var value = card.type_arg;
                switch(card.type) {
                    case "saphir":
                        // code block
                        var color=1;
                        break;
                    case "rubis":
                        // code block
                        var color=2;
                        break;
                    case "Emeraudes":
                        // code block
                        var color=3;
                        break;
                    case "Diamond":
                        // code block
                        var color=4;
                        break;
                }

                stock.addToStockWithId(this.getCardUniqueId(color, value), card.id);

            }

            this.playerPile[player.id] = stock;

            });





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
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */


        ///////////////////////////////////////////////////
        //// Player's action

        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
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
