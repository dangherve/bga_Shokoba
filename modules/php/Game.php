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
            "lastCardPlay" => 10,
            "tableCardPosition" => 11,
            "HandSize" => 12,
            "TableSize" => 13,
            "lastTakenPlayer" => 14,
            "finalScore" => 15,

        ]);

        $this->cards = $this->getNew("module.common.deck");
        $this->cards->init("card");

        $this->translatedColors = [
            0 => clienttranslate('Total'),
            1 => clienttranslate('Saphir'),
            2 => clienttranslate('Rubis'),
            3 => clienttranslate('Emeraudes'),
            4 => clienttranslate('Diamond'),
        ];
    }


    function _checkActivePlayer()
    {
        if ($this->getActivePlayerId() !== $this->getCurrentPlayerId()) {
            throw new feException(self::_("Unexpected Error: you are not the active player"), true);
        }
    }

    public function getCardUniqueId (int $color, int $value) : int{
        return (($color - 1) * 10 + ($value-1)) ;
    }

    /**
     *
     * Player can leave a card on the table
     *
     * @throws BgaUserException
     */
    public function actLeaveCard(int $card_id): void
    {
        $this->checkAction('actLeaveCard');
        $player_id = $this->getActivePlayerId();
        $this->_checkActivePlayer();

        $tableCardPosition=$this->getGameStateValue("tableCardPosition")+1;

        $this->cards->moveCard($card_id, 'table',$tableCardPosition);

        $this->setGameStateValue("tableCardPosition", $tableCardPosition);

        $card=$this->cards->getCard($card_id);

        $this->setGameStateValue("lastCardPlay", (int)$card_id);
        //clienttranslate('leave card '.$card['type']." ".$card['type_arg'])
        $this->notifyAllPlayers(
            'leaveCard',
            '',
            [
                'card_id' => $card_id,
                'card_type' => $this->getCardUniqueId((int)$card['type'],(int)$card['type_arg']),
                'player_id' => $player_id,
            ]
        );
        $this->gamestate->nextState('nextPlayer');
    }

    public function getCardValue($card_id)
    {
        return($this->cards->getCard($card_id)['type_arg']);
    }

    /**
     *
     * Player can take cards on the table with the last played card if the previous player
     * did not take it or with one of his card
     *
     * Advance rule not implemented yet ie take the highest card is mandatory
     * ie:
     *   card on table  1 2 4 7 10
     *   player card  2 6 10

     -> take 2 with the 2
     -> take 2 4 with the 6
     -> take 1 2 7 with the 10 => not possible with the advace rule
     -> take 10 with 10

     * @throws BgaUserException
     */
    public function actTakeCard(string $playerCard_id,  string $tableCard_ids): void
    {
        $actionSuccess=false;
        $lastCardplayed=false;
        $this->checkAction('actTakeCard');
        $player_id = $this->getActivePlayerId();
        $this->_checkActivePlayer();

        $lastCardPlay = $this->getGameStateValue("lastCardPlay");
        $liste_tableCard_ids=explode(',',$tableCard_ids);

        //player take card(s) with one of his
        if ($playerCard_id != -1){
            $value=0;

            //check i ask for cards on the table
            if(strlen($tableCard_ids)==0){
                throw new \BgaUserException(self::_("You did not select any card on table"), true);
            }

            // calculate total value
            foreach ($liste_tableCard_ids as $tableCard_id){
                $value=$value+ (int)SELF::getCardValue($tableCard_id);
            }

            //check if value is a match
            if($value != SELF::getCardValue($playerCard_id)){
                throw new \BgaUserException(self::_("Taken card(s) value did not match played card"), true);
            }

        //take with last card
        }elseif (($lastCardPlay != -1) && (strlen($tableCard_ids)!=0)){
            $value=0;

            //calculate value and check if last card played
            foreach ($liste_tableCard_ids as $tableCard_id) {
                if ($tableCard_id != $lastCardPlay){
                    $value=$value+(int)SELF::getCardValue($tableCard_id);
                }else{
                    $lastCardplayed=true;
                }
            }

            //last card played check
            if(!$lastCardplayed){
                throw new \BgaUserException(self::_("You did not select the last played card"), true);
            }

            //check if value is a match
            if ( (int)SELF::getCardValue($lastCardPlay) != $value ){
                throw new \BgaUserException(self::_("Taken card(s) value did not match the last played card"), true);
            }
        }else{
            //no card selected
            throw new \BgaUserException(self::_("You did not select any card in your hand or the last played card"), true);
        }

        //move player card in his taken pile
        if ($playerCard_id != -1){
            $this->cards->moveCard($playerCard_id, 'taken',$player_id);
        }

        //move table card(s) in his taken pile
        foreach ($liste_tableCard_ids as $tableCard_id) {
            $this->cards->moveCard($tableCard_id, 'taken',$player_id);
        }

        //update cards
        $this->notifyAllPlayers(
            'takeCard','',
            [
                'tableCard_id' => $liste_tableCard_ids,
                'playerCard_id' => $playerCard_id,
                'player_id' => $player_id,
            ]
        );

        //SHOKOBA check ie all card in table taken current player win one point
        if($this->cards->countCardInLocation('table')==0){
            $sql = "UPDATE player
                    SET player_score = player_score +1 WHERE player_id=".$player_id;
            $this->DbQuery( $sql );

            //update score
            $newScores = $this->getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
            $this->notifyAllPlayers( "newScores", "", array(
                "scores" => $newScores
            ));

            $this->incStat(1,"Shokoba",$player_id);

        }

        $this->setGameStateValue("lastTakenPlayer", $player_id);

        //next state nextPlayer or bonus turn if we take the las card played
        if ($playerCard_id != -1){
            $this->gamestate->nextState('nextPlayer');
        }else{
            $this->gamestate->nextState('playerTurn');
        }

    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * Basic game calculation progression might be improve later
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        $maxScores = self::getUniqueValueFromDB("SELECT MAX(player_score) FROM player");
        $finalScore = $this->getGameStateValue("finalScore");

        $progression = $maxScores/$finalScore*100;

        return $progression;
    }


    /**
     *
     * Debug function
     *
     * @throws BgaUserException
     */

    public function debug_emptyDeck() {
        $players = $this->loadPlayersBasicInfos();

        $handSize = (int)$this->cards->countCardInLocation('deck')/ count($players);

        foreach ($players as $player_id => $player) {
            $this->cards->pickCardsForLocation($handSize,'deck', 'taken', $player_id);
        }
    }

    /**
     *
     * Calculate point
     *
     * might have a solution to improve calculation and ui show
     *
     * @throws BgaUserException
     */

    public function score(): void {
        $players = $this->loadPlayersBasicInfos();

        $countCard=[];
        $points=[];
        $nameRow = [''];

        $totalRow = [['str' => $this->translatedColors[0], 'args' => []]];
        $saphirRow = [['str' => $this->translatedColors[1], 'args' => []]];
        $rubisRow = [['str' => $this->translatedColors[2], 'args' => []]];
        $EmeraudesRow = [['str' => $this->translatedColors[3], 'args' => []]];
        $DiamondRow = [['str' => $this->translatedColors[4], 'args' => []]];

        //init score
        foreach ($players as $player_id => $player) {
            $points[$player_id]=0;
        }

        //max of each card type
        for ($color = 1; $color <= 4; $color++) {
            foreach ($players as $player_id => $player) {
                $countCard[$color][$player_id]=sizeof($this->cards->getCardsOfTypeInLocation($color,null,'taken',$player_id));
            }
            $maxs = array_keys($countCard[$color], max($countCard[$color]));
            $points[$maxs[0]]=$points[$maxs[0]]+1;
            $countCard[$color][$maxs[0]]=(string)$countCard[$color][$maxs[0]].'✓';
            $this->incStat(1,$this->translatedColors[$color],$maxs[0]);

        }

        //max card
        $colorRow[0]= [['str' => $this->translatedColors[0], 'args' => []]];
        foreach ($players as $player_id => $player) {
            $countCard[0][$player_id]=$this->cards->countCardInLocation('taken',$player_id);
        }
        $maxs = array_keys($countCard[0], max($countCard[0]));
        $points[$maxs[0]]=$points[$maxs[0]]+1;
        $countCard[0][$maxs[0]]=(string)$countCard[0][$maxs[0]].'✓';
        $this->incStat(1,$this->translatedColors[0],$maxs[0]);

        foreach ($players as $player_id => $player) {
            // Header line
            $nameRow[] = [
                'str' => '${player_name}',
                'args' => ['player_name' => $this->getPlayerNameById($player_id)],
                'type' => 'header',
            ];

            $totalRow[] = $countCard[0][$player_id];
            $saphirRow[] = $countCard[1][$player_id];
            $rubisRow[] = $countCard[2][$player_id];
            $EmeraudesRow[] = $countCard[3][$player_id];
            $DiamondRow[] = $countCard[4][$player_id];


        }

        $table = [$nameRow,$saphirRow,$rubisRow,$EmeraudesRow,$DiamondRow,$totalRow];

        $this->notifyAllPlayers("tableWindow", '', [
            "id" => 'finalScoring',
            "title" => "",
            "table" => $table,
            "closing" => clienttranslate("Close"),
        ]);


        foreach ($players as $player_id => $player) {
            self::DbQuery(sprintf("UPDATE player SET player_score = player_score + %d WHERE player_id = '%s'", $points[$player_id], $player_id));
        }
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
        }else if($this->cards->countCardInLocation('deck')==0 && $this->cards->countCardInLocation('hand')==0){
            $this->gamestate->nextState("endHand");
        }else{
            $this->gamestate->nextState("newTurn");
        }
    }

    public function stEndHand(): void {

        $this->cards->moveAllCardsInLocation("table", "taken",null,$this->getGameStateValue("lastTakenPlayer"));
        $this->score();

        $newScores = self::getCollectionFromDb("SELECT player_id, player_score FROM player", true );
        $this->notifyAllPlayers( "newScores", "", array(
                "scores" => $newScores
        ) );

        $endgame = false;

        ///// Test if this is the end of the game
        foreach ( $newScores as $player_id => $score ) {
            if ($score >= $this->getGameStateValue("finalScore") ) {
                // Trigger the end of the game !
                $endgame = true;
            }
        }

        if($endgame){
            $this->gamestate->nextState("endGame");
        }else{
            $this->incStat(1,"turns_number");
            $this->gamestate->nextState("newTable");
        }

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

        // Hands
        $result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);

        //order_by not working or did no have correct syntax ,$order_by='id'
        $result['table'] = $this->cards->getCardsInLocation($location='table');

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

        $NumberOfPlayer=sizeof($players);
        $this->setGameStateInitialValue('lastCardPlay', -1);
        $this->setGameStateInitialValue('HandSize', 3);
        $this->setGameStateInitialValue('TableSize', 4);

        switch ($NumberOfPlayer){
            case 2:
                $this->setGameStateInitialValue('finalScore', 7);
                break;
            case 3:
                $this->setGameStateInitialValue('finalScore', 6);
                break;
            case 4:
                //if (TEAM){
                    //$this->setGameStateInitialValue('finalScore', 4);
                //}else{
                    $this->setGameStateInitialValue('finalScore', 7);
                //}
                break;
        }

        $this->initializeDeck();


        // Init game statistics.
        //
        $this->initStat("table", "turns_number", 0);
        $this->initStat("player", "Saphir", 0);
        $this->initStat("player", "Rubis", 0);
        $this->initStat("player", "Emeraudes", 0);
        $this->initStat("player", "Diamond", 0);
        $this->initStat("player", "Total", 0);
        $this->initStat("player", "Shokoba", 0);

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

        $this->setGameStateValue("lastCardPlay", -1);

        // Deal cards to each player (and signal the UI to clean-up)
        //self::notifyAllPlayers('cleanUp', clienttranslate('${player_name} deals a new hand.'));

		// Create deck, shuffle it and give initial cards
        $players = self::loadPlayersBasicInfos();

        $this->initializeGameTable();

        // Notify player about his cards
        $this->notifyAllPlayers( 'newTable', '', array(
            'table' => $this->cards->getCardsInLocation('table'),
        ));



       $this->gamestate->nextState('newTurn');
    }



    function stNewHand() {

        $players = self::loadPlayersBasicInfos();

        $this->initializePlayersHand();

        foreach($players as $player_id => $player) {
            // Notify player about his cards
            $hand = $this->getPlayerHand($player_id);
            self::notifyPlayer($player_id, 'newHand', '', array(
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

    protected function initializePlayersHand(): void
    {
        $players = $this->loadPlayersBasicInfos();
        $handSize = $this->getGameStateValue("HandSize");
        foreach ($players as $player_id => $player) {
            $this->cards->pickCards($handSize, 'deck', $player_id);
        }
    }

    protected function initializeGameTable(): void
    {
        $tableSize = $this->getGameStateValue("TableSize");

        for ($i=0;$i<$tableSize;$i++){
            $this->cards->pickCardForLocation('deck', 'table',$i);
        }
        $this->setGameStateValue("tableCardPosition", $tableSize);
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
            $player_hand = $this->getPlayerHand($active_player);
            //to do improve random pick instead of the first one

            if (sizeof($player_hand)>0){
                $card_id=array_key_first($player_hand);

                self::actLeaveCard((int)$card_id);
                }else{
                    $this->debug( "## ERROR empty hand   ###" );
                }
        }

    }
}
