<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * shokoba implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */
declare(strict_types=1);

namespace Bga\Games\shokoba;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

class Game extends \Table
{
    private static array $CARD_TYPES;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            "toto" => 10,

        ]);

        $this->cards = $this->getNew("module.common.deck");
        $this->cards->init("card");

        $this->tableCardPosition=0;

        $this->turnCounter=0;

        $this->lastCardPlay=-1;

        $this->translatedColors = [
            1 => clienttranslate('saphir'),
            2 => clienttranslate('rubis'),
            3 => clienttranslate('Emeraudes'),
            4 => clienttranslate('Diamond'),
        ];
    }
    public function getHandSize(): int
    {
        return 3;
    }
    function getTableSize(): int
    {
        return 4;
    }
    function _checkActivePlayer()
    {
        if ($this->getActivePlayerId() !== $this->getCurrentPlayerId()) {
            throw new feException(self::_("Unexpected Error: you are not the active player"), true);
        }
    }


    /**
     * Player action, example content.
     *
     * In this scenario, each time a player plays a card, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     *
     * @throws BgaUserException
     */
    public function actLeaveCard(int $card_id): void
    {
        $this->checkAction('actLeaveCard');
        $player_id = $this->getActivePlayerId();
        $this->_checkActivePlayer();
        $currentCard = $this->_checkAndGetPlayerCard($card_id, $player_id);

        $this->tableCardPosition++;

        $this->cards->moveCard($card_id, 'table',$this->tableCardPosition);


        $this->notifyAllPlayers(
            'leaveCard',
            clienttranslate('LeaveCard'),
            [
                'card_id' => $card_id,
                'player_id' => $player_id,
            ]
        );
        $this->gamestate->nextState('nextPlayer');
    }


    public function actTakeCard(int $card_id): void
    {
        $this->checkAction('actTakeCard');

        $player_id = $this->getActivePlayerId();
        $this->_checkActivePlayer();
        $currentCard = $this->_checkAndGetPlayerCard($card_id, $player_id);


        $this->cards->moveCard($card_id, 'table');


        $this->notifyAllPlayers(
            'leaveCard',
            clienttranslate('LeaveCard'),
            [

            ]
        );
        $this->gamestate->nextState('nextPlayer');
    }

   function _checkAndGetPlayerCard($card_id, $player_id)
    {
        $playerCard = null;
        $playerHand = $this->cards->getCardsInLocation('hand', $player_id);
        foreach ($playerHand as $card) {
            if ((int)$card['id'] === $card_id) {
                $playerCard = $card;
            }
        }
        if ($playerCard === null) {
            throw new feException(self::_("Unexpected Error: Invalid Card"), true);
        }

        return $playerCard;
    }


    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `playerTurn` game state.
     *
     * @return array
     * @see ./states.inc.php
     */
    public function argPlayerTurn(): array
    {
        // Get some values from the current game situation from the database.

        return [
            "playableCardsIds" => [1, 2],
        ];
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }

    /**
     * Game state action, example content.
     *
     * The action method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */
    public function stNextPlayer(): void {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Give some extra time to the active player when he completed an action
        $this->giveExtraTime($player_id);

        $this->activeNextPlayer();

        if ( $this->cards->countCardInLocation('hand')>0){
            $this->gamestate->nextState("playerTurn");
        }else if($this->cards->countCardInLocation('deck')==0){
            $this->gamestate->nextState("newTable");
        }else{
            $this->gamestate->nextState("newTurn");
        }





        // Go to another gamestate
        // Here, we would detect if the game is over, and in this case use "endGame" transition instead

    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
//       if ($from_version <= 1404301345)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
//
//       if ($from_version <= 1405061421)
//       {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//       }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas()
    {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();
        $player_id = $this->getCurrentPlayerId();
        $result['player_id'] = $player_id;

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );

        // TODO: Gather all information about current game situation (visible by player $current_player_id).


        // Hands
        $result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);

        $result['table'] = $this->cards->getCardsInLocation('table');

        return $result;
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName()
    {
        return "shokoba";
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.


        $this->initializeDeck();


        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.

        // Dummy content.
        // $this->initStat("table", "table_teststat1", 0);
        // $this->initStat("player", "player_teststat1", 0);

        // TODO: Setup the initial game situation here.

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }


    protected function getPlayerHand($player_id): array
    {
        $cards = $this->cards->getCardsInLocation('hand', $player_id);

        return $cards;
    }


    function stNewTable() {

        // Take back all cards (from any location => null) to deck and shuffle
        $this->cards->moveAllCardsInLocation(null, "deck");
        $this->cards->shuffle('deck');

        // Deal cards to each player (and signal the UI to clean-up)
        self::notifyAllPlayers('cleanUp', clienttranslate('${player_name} deals a new hand.'));

		// Create deck, shuffle it and give initial cards
        $players = self::loadPlayersBasicInfos();

        $this->initializeGameTable();

/*
        foreach($players as $player_id => $player) {
            // Notify player about his cards
            self::notifyPlayer($player_id, 'newTable', clienttranslate('-- Your cards are:&nbsp;<br />${cards}'), array(
                'cards' => self::listCardsForNotification($hand),
                'hand' => $hand,
            ));
        }
*/
       $this->gamestate->nextState('newTurn');
    }



    function stNewHand() {


		// Create deck, shuffle it and give initial cards
        $players = self::loadPlayersBasicInfos();

        $this->initializePlayersHand();

        foreach($players as $player_id => $player) {
            // Notify player about his cards


            $hand = $this->getPlayerHand($player_id);

//${cards}
            self::notifyPlayer($player_id, 'newHand', clienttranslate('TOTO'), array(
//                'cards' => self::listCardsForNotification($hand),
                'hand' => $hand,
            ));

        }

       $this->gamestate->nextState('playerTurn');
    }

    // Create and shuffle deck
    protected function initializeDeck()
    {

        $cards = [];

        //May be a way to improve this
        //saphir card 1->10 3 each no 7
        for ($value = 1; $value <= 10; ++$value) {
            if ($value != 7) {
                $cards[] = ['type' => 1, 'type_arg' => $value, 'nbr' => 3];
            }
        }

        //rubis card 1->10 1 each no 7
        for ($value = 1; $value <= 10; ++$value) {
            if ($value != 7) {
                $cards[] = ['type' => 2, 'type_arg' => $value, 'nbr' => 1];
            }
        }

        //Emeraudes 3 x 7 card
        $cards[] = ['type' => 3, 'type_arg' => 7, 'nbr' => 3];

        //Diamond
        $cards[] = ['type' => 4, 'type_arg' => 7, 'nbr' => 1];


        $this->cards->createCards($cards, 'deck');

    }


//            $hand = $this->cards->pickCards(self::getGameStateValue('card_number_for_each_player'), 'deck', $player_id );
//for variant in option


    protected function initializePlayersHand(): void
    {
        $this->turnCounter=0;
        $players = $this->loadPlayersBasicInfos();
        $handSize = $this->getHandSize();
        foreach ($players as $player_id => $player) {
            $this->cards->pickCards($handSize, 'deck', $player_id);
        }
    }

    protected function initializeGameTable(): void
    {
        $tableSize = $this->getTableSize();

        for ($i=0;$i<$tableSize;$i++){
            $this->cards->pickCardForLocation('deck', 'table',$i);
        }
        $this->tableCardPosition=$tableSize;
    }


    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default:
                {
                    $this->gamestate->nextState("zombiePass");
                    break;
                }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }
}
