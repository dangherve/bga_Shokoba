<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * shokoba implementation : © <Herve Dang> <dang.herve@gmail.com>
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

    private int $defautColor = 3;
    private int $defautValue = 1;

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
            "dealer" => 16,

            //game option
            "teamPlay" =>100,
            "advancedTeamPlay" =>101,
            "advancedCard" =>102,
            "officialScore" =>103,
            "score" =>104,
            "showTakenCard" =>105,

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
            throw new \BgaUserException(self::_("Unexpected Error: you are not the active player"), true);
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

        $this->notifyAllPlayers(
            'leaveCard',
            clienttranslate('${player_name} lays a ${value} ${symbol} card into the center of the table.'),
            [
                'value' => $card['type_arg'],
                'symbol' => $card['type'],

                'player_name' => $this->getActivePlayerName(),
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

     * TODO might improve the shokoba team calculation

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

        $cardsOnTable = $this->cards->getCardsInLocation('table');

        $cardsValueOnTable=[];
        $cardsTaken=[];

        foreach($cardsOnTable as $card){
            $cardsValueOnTable[]=(int)$card['type_arg'];
        }

        //player take card(s) with one of his
        if ($playerCard_id != -1){
            $value=0;

            //check i ask for cards on the table
            if(strlen($tableCard_ids)==0){
                throw new \BgaUserException(self::_("You did not select any card on table"), true);
            }

            $cardPlayed=$this->cards->getCard($playerCard_id);
            $message='${player_name} plays a ${value} ${symbol} card into the center of the table and collects ${cardsTakenUI}';

            // calculate total value
            foreach ($liste_tableCard_ids as $tableCard_id){
                $value=$value+ (int)SELF::getCardValue($tableCard_id);
                $cardsTaken[]=(int)SELF::getCardValue($tableCard_id);
                $cardsTakenUI[]=$this->cards->getCard($tableCard_id);
            }

            //check if value is a match
            if($value != SELF::getCardValue($playerCard_id)){
                throw new \BgaUserException(self::_("Taken card(s) value did not match played card"), true);
            }

            if (($this->getGameStateValue('advancedCard') == 2) &&
                in_array((int)SELF::getCardValue($playerCard_id),$cardsValueOnTable) &&
                !in_array((int)SELF::getCardValue($playerCard_id),$cardsTaken)
            ){
                throw new \BgaUserException(self::_("You need to take the card of the same value first"), true);
            }

        //take with last card
        }elseif (($lastCardPlay != -1) && (strlen($tableCard_ids)!=0)){
            $value=0;

            $message='${player_name} plays the last card ${value} ${symbol} and collects ${cardsTakenUI} and had to play again';
            //calculate value and check if last card played
            foreach ($liste_tableCard_ids as $tableCard_id) {
                $card=$this->cards->getCard($tableCard_id);
                if ($tableCard_id != $lastCardPlay){
                    $value=$value+(int)SELF::getCardValue($tableCard_id);
                    $cardsTaken[]=(int)SELF::getCardValue($tableCard_id);
                    $cardsTakenUI[]=$this->cards->getCard($tableCard_id);
                }else{
                    $lastCardplayed=true;
                    $cardPlayed['type_arg']=$card['type_arg'];
                    $cardPlayed['type']=$card['type'];
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

            if (($this->getGameStateValue('advancedCard') == 2) &&
                in_array((int)SELF::getCardValue($lastCardPlay),$cardsValueOnTable) &&
                !in_array((int)SELF::getCardValue($lastCardPlay),$cardsTaken)
            ){
                throw new \BgaUserException(self::_("You need to take the card of the same value first"), true);
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
        //TO DO improve card play and taken
        $this->notifyAllPlayers(
            'takeCard',clienttranslate($message),
            [
                'value' => $cardPlayed['type_arg'],
                'symbol' =>$cardPlayed['type'],
                'cardsTakenUI' => $cardsTakenUI,

                'player_name' => $this->getActivePlayerName(),
                'tableCard_id' => $liste_tableCard_ids,
                'playerCard_id' => $playerCard_id,
                'player_id' => $player_id,
            ]
        );


        if ($this->getGameStateValue('showTakenCard') == 2){

            $takenCards['saphir']=sizeof($this->cards->getCardsOfTypeInLocation(1,null,'taken',$player_id));
            $takenCards['rubis']=sizeof($this->cards->getCardsOfTypeInLocation(2,null,'taken',$player_id));
            $takenCards['emeraudes']=sizeof($this->cards->getCardsOfTypeInLocation(3,null,'taken',$player_id));
            $takenCards['diamond']=sizeof($this->cards->getCardsOfTypeInLocation(4,null,'taken',$player_id));

            self::notifyPlayer($player_id, 'takenCards', '', array(
                'takenCards' => $takenCards,
            ));

        }


        //SHOKOBA check ie all card in table taken current player win one point
        if($this->cards->countCardInLocation('table')==0){
            $sql = "UPDATE player
                    SET player_score = player_score +1 WHERE player_id=".$player_id;
            $this->DbQuery( $sql );

            // add point to team mate also
            if ($this->getGameStateValue('teamPlay') == 2){
                $players = $this->loadPlayersBasicInfos();
                $i=0;
                $j=0;
                foreach ($players as $pid => $player) {
                    if(($i%2)==0){
                        $teamA[$j]=$pid;
                    }else{
                        $teamB[$j]=$pid;
                        $j++;
                    }
                    $i++;
                }

                if (in_array($player_id, $teamA)) {
                    if($teamA[0]==$player_id){
                        $player_id2=$teamA[1];
                    }else{
                        $player_id2=$teamA[0];
                    }
                }else{
                    if($teamB[0]==$player_id){
                        $player_id2=$teamB[1];
                    }else{
                        $player_id2=$teamB[0];
                    }
                }
                $sql = "UPDATE player
                        SET player_score = player_score +1 WHERE player_id=".$player_id2;
                $this->DbQuery( $sql );
            }

            //update score
            $newScores = $this->getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
            $this->notifyAllPlayers( "newScores", clienttranslate('${player_name} empty the table so score a ${shokoba}'), array(
                'player_name' => $this->getActivePlayerName(),
                "shokoba" => "SHOKOBA",
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
        $countCardTeam=[];

        $points=[];
        $nameRow = [''];

        $totalRow = [['str' => $this->translatedColors[0], 'args' => []]];
        $saphirRow = [['str' => $this->translatedColors[1], 'args' => []]];
        $rubisRow = [['str' => $this->translatedColors[2], 'args' => []]];
        $EmeraudesRow = [['str' => $this->translatedColors[3], 'args' => []]];
        $DiamondRow = [['str' => $this->translatedColors[4], 'args' => []]];


        $points[0]=0;
        $points[1]=0;
        $i=0;
        $j=0;
        //init score
        foreach ($players as $player_id => $player) {
            $points[$player_id]=0;
            if(($i%2)==0){
                $teamA[$j]=$player_id;
            }else{
                $teamB[$j]=$player_id;
                $j++;
            }
            $i++;
        }
//"${player_name} earns 1 gemstone for the most diamonds (3 cards)"
//"${player_1} and ${player_2} are tied on number of rubies, no gemstones for rubies this round."
        //max of each card type
        for ($color = 1; $color <= 4; $color++) {
            $team=0;
            foreach ($players as $player_id => $player) {
                $countCard[$color][$player_id]=sizeof($this->cards->getCardsOfTypeInLocation($color,null,'taken',$player_id));
            }

            if ($this->getGameStateValue('teamPlay') == 2){
                $countCardTeam[$color][0]=$countCard[$color][$teamA[0]]+$countCard[$color][$teamA[1]];
                $countCardTeam[$color][1]=$countCard[$color][$teamB[0]]+$countCard[$color][$teamB[1]];
                if($countCardTeam[$color][0]>$countCardTeam[$color][1]){
                    $points[0]=$points[0]+1;
                    $countCardTeam[$color][0]=(string)$countCardTeam[$color][0].'✓';
                }else{
                    $points[1]=$points[1]+1;
                    $countCardTeam[$color][1]=(string)$countCardTeam[$color][1].'✓';
                }
                $maxs = array_keys($countCard[$color], max($countCard[$color]));

                //stat will be calculated as indivdual not team
                if(sizeof($maxs)==1){
                    $this->incStat(1,$this->translatedColors[$color],$maxs[0]);
                }

            }else{
                $maxs = array_keys($countCard[$color], max($countCard[$color]));

                if(sizeof($maxs)==1){
                    $points[$maxs[0]]=$points[$maxs[0]]+1;
                    $countCard[$color][$maxs[0]]=(string)$countCard[$color][$maxs[0]].'✓';
                    $this->incStat(1,$this->translatedColors[$color],$maxs[0]);
                }
            }
        }

        //max card
        $colorRow[0]= [['str' => $this->translatedColors[0], 'args' => []]];
        foreach ($players as $player_id => $player) {
            $countCard[0][$player_id]=$this->cards->countCardInLocation('taken',$player_id);
        }


        if ($this->getGameStateValue('teamPlay') == 2){
            $countCardTeam[0][0]=$countCard[0][$teamA[0]]+$countCard[0][$teamA[0]];
            $countCardTeam[0][1]=$countCard[0][$teamB[0]]+$countCard[0][$teamB[0]];
            if($countCardTeam[0][0]>$countCardTeam[0][1]){
                $points[0]=$points[0]+1;
                $countCardTeam[0][0]=(string)$countCardTeam[0][0].'✓';
            }else{
                $points[1]=$points[1]+1;
                $countCardTeam[0][1]=(string)$countCardTeam[0][1].'✓';
            }
            $maxs = array_keys($countCard[0], max($countCard[0]));

            //stat will be calculated as indivdual not team
            if(sizeof($maxs)==1){
                $this->incStat(1,$this->translatedColors[0],$maxs[0]);
            }

        }else{
            $maxs = array_keys($countCard[0], max($countCard[0]));

            if(sizeof($maxs)==1){
                $points[$maxs[0]]=$points[$maxs[0]]+1;
                $countCard[0][$maxs[0]]=(string)$countCard[0][$maxs[0]].'✓';
                $this->incStat(1,$this->translatedColors[0],$maxs[0]);
            }
        }



        if ($this->getGameStateValue('teamPlay') == 2){

                $nameRow[] = [
                    'str' => '${player_name1} ${player_name2}',
                    'args' => ['player_name1' => $this->getPlayerNameById($teamA[0]),
                               'player_name2' => $this->getPlayerNameById($teamA[1])
                    ],
                    'type' => 'header',
                ];

                $nameRow[] = [
                    'str' => '${player_name1} ${player_name2}',
                    'args' => ['player_name1' => $this->getPlayerNameById($teamB[0]),
                               'player_name2' => $this->getPlayerNameById($teamB[1])
                    ],
                    'type' => 'header',
                ];

                $totalRow[] = $countCardTeam[0][0].'('.$countCard[0][$teamA[0]].','.$countCard[0][$teamA[1]].')';
                $saphirRow[] = $countCardTeam[1][0].'('.$countCard[1][$teamA[0]].','.$countCard[1][$teamA[1]].')';
                $rubisRow[] = $countCardTeam[2][0].'('.$countCard[2][$teamA[0]].','.$countCard[2][$teamA[1]].')';
                $EmeraudesRow[] = $countCardTeam[3][0].'('.$countCard[3][$teamA[0]].','.$countCard[3][$teamA[1]].')';
                $DiamondRow[] = $countCardTeam[4][0].'('.$countCard[4][$teamA[0]].','.$countCard[4][$teamA[1]].')';

                $totalRow[] = $countCardTeam[0][1].'('.$countCard[0][$teamB[0]].','.$countCard[0][$teamB[1]].')';
                $saphirRow[] = $countCardTeam[1][1].'('.$countCard[1][$teamB[0]].','.$countCard[1][$teamB[1]].')';
                $rubisRow[] = $countCardTeam[2][1].'('.$countCard[2][$teamB[0]].','.$countCard[2][$teamB[1]].')';
                $EmeraudesRow[] = $countCardTeam[3][1].'('.$countCard[3][$teamB[0]].','.$countCard[3][$teamB[1]].')';
                $DiamondRow[] = $countCardTeam[4][1].'('.$countCard[4][$teamB[0]].','.$countCard[4][$teamB[1]].')';




        }else{
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
        }

        $table = [$nameRow,$saphirRow,$rubisRow,$EmeraudesRow,$DiamondRow,$totalRow];

        $this->notifyAllPlayers("tableWindow", clienttranslate("There are no more cards to deal. The round ends."), [
            "id" => 'finalScoring',
            "title" => "",
            "table" => $table,
            "closing" => clienttranslate("Close"),
        ]);

        if ($this->getGameStateValue('teamPlay') == 2){
            self::DbQuery(sprintf("UPDATE player SET player_score = player_score + %d WHERE player_id = '%s' or player_id = '%s'",
            $points[0], $teamA[0], $teamA[1]));
            self::DbQuery(sprintf("UPDATE player SET player_score = player_score + %d WHERE player_id = '%s' or player_id = '%s'",
            $points[1], $teamB[0], $teamB[1]));
        }else{
            foreach ($players as $player_id => $player) {
                self::DbQuery(sprintf("UPDATE player SET player_score = player_score + %d WHERE player_id = '%s'", $points[$player_id], $player_id));
            }
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

    public function stDummyAction(): void{
        $this->gamestate->nextState("endGame");
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
            $this->gamestate->nextState("endScore");
        }else{
            $this->incStat(1,"turns_number");
            $this->gamestate->changeActivePlayer($this->getPlayerAfter($this->getGameStateValue('dealer')));
            $this->setGameStateInitialValue("dealer", $this->getPlayerAfter($this->activeNextPlayer()));
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
        $player_id = $this->getCurrentPlayerId();
        $result['player_id'] = $player_id;

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );

        // Hands
        $players = $this->loadPlayersBasicInfos();
        $result['nbplayer']=sizeof($players);
        foreach ($players as $other_id => $other) {
            $isSelf = ((int) $other_id) === ((int) $player_id);
            $isSpectator = !isset($players[$player_id]);

            $shouldCardsBeHidden = !($isSelf || $isSpectator);
            $result['hand' . $other_id] = $this->getPlayerHand($other_id, $shouldCardsBeHidden);
        }

        // Hands
        $result['hand'] = $this->cards->getCardsInLocation('hand', $player_id);

        // deck
        $result['deck'] = $this->getDeckCard();

        //order_by not working or did no have correct syntax ,$order_by='id'
        $result['table'] = $this->cards->getCardsInLocation($location='table');

        $result['showTakenCard'] = $this->getGameStateValue('showTakenCard');
        if ($this->getGameStateValue('showTakenCard') == 2){
            $takenCards['saphir']=sizeof($this->cards->getCardsOfTypeInLocation(1,null,'taken',$player_id));
            $takenCards['rubis']=sizeof($this->cards->getCardsOfTypeInLocation(2,null,'taken',$player_id));
            $takenCards['emeraudes']=sizeof($this->cards->getCardsOfTypeInLocation(3,null,'taken',$player_id));
            $takenCards['diamond']=sizeof($this->cards->getCardsOfTypeInLocation(4,null,'taken',$player_id));
            $result['takenCards']=$takenCards;
        }

        return $result;
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

        if($this->getGameStateValue('officialScore') == 1){
            switch ($NumberOfPlayer){
                case 2:
                    $this->setGameStateInitialValue('finalScore', 7);
                    break;
                case 3:
                    $this->setGameStateInitialValue('finalScore', 6);
                    break;
                case 4:
                    if ($this->getGameStateValue('teamPlay') == 2){
                        $this->setGameStateInitialValue('finalScore', 7);
                        if ($this->getGameStateValue('advancedTeamPlay') == 2){
                            $this->setGameStateInitialValue('HandSize', 9);
                        }
                    }else{
                        $this->setGameStateInitialValue('finalScore', 4);
                    }
                    break;
            }
        }else{
            $this->setGameStateInitialValue('finalScore', $this->getGameStateValue('score'));
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

        $this->setGameStateInitialValue("dealer", $this->activeNextPlayer());

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }


    protected function getPlayerHand($player_id, bool $shouldCardsBeHidden = false): array
    {
        $cards = $this->cards->getCardsInLocation('hand', $player_id);

        foreach ($cards as $i => $value) {
            if ($shouldCardsBeHidden) {
                $cards[$i]['type'] = $this->defautColor;
                $cards[$i]['type_arg'] = $this->defautValue;
            }
        }
        return $cards;
    }

    protected function getDeckCard(): array
    {
        $cards = $this->cards->getCardsInLocation('deck');

        foreach ($cards as $i => $value) {
                $cards[$i]['type'] = $this->defautColor;
                $cards[$i]['type_arg'] = $this->defautValue;
        }
        return $cards;
    }

    function stNewTable() {

        // Take back all cards (from any location => null) to deck and shuffle
        $this->cards->moveAllCardsInLocation(null, "deck");
        $this->cards->shuffle('deck');

        $this->setGameStateValue("lastCardPlay", -1);

		// Create deck, shuffle it and give initial cards
        $players = self::loadPlayersBasicInfos();

        $this->initializeGameTable();

        // Notify player about his cards
        $this->notifyAllPlayers( 'newTable', clienttranslate("A new round starts."), array(
            'table' => $this->cards->getCardsInLocation('table'),
            'deck' => $this->getDeckCard(),
        ));

        if ($this->getGameStateValue('showTakenCard') == 2){

            $takenCards['saphir']=0;
            $takenCards['rubis']=0;
            $takenCards['emeraudes']=0;
            $takenCards['diamond']=0;

            self::notifyAllPlayers( 'takenCards', '', array(
                'takenCards' => $takenCards,
            ));

        }

       $this->gamestate->nextState('newTurn');
    }



    function stNewHand() {

        $players = self::loadPlayersBasicInfos();

        $this->initializePlayersHand();

        foreach($players as $player_id => $player) {
            // Notify player about his cards

            self::notifyallPlayers('newHand', '', array(
                'player_id' => $player_id,
                'hand' => $this->getPlayerHand($player_id, true),
            ));

            $hand = $this->getPlayerHand($player_id);
            self::notifyPlayer($player_id, 'newHandPrivate', clienttranslate('${player_name} deals a new hand'), array(
                'player_name' => $this->getPlayerNameById($this->getGameStateValue('dealer')),
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
