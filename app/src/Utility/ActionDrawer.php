<?php

namespace App\Utility;

class ActionDrawer
{
    private static $handId;
    private static $playerId;
    private static $gameId;

    public static function getActionData(Database $database, $gameId, $playerId)
    {
        $nextAction = GameState::whatIsMyNextAction($database, $gameId);
        switch ($nextAction) {
            case 'guess':
                $action = 'guess';
                $hand = ActionDrawer::startHandIfNeeded($database, $gameId, $playerId);
                $cantGuess = DataRequest::whatNumberCannotBeGuessed($gameId, $hand);
                return ['action' => $nextAction, 'guess_cant_say' => $cantGuess];
                break;
            case 'trumps':
                $action = 'trumps';
                break;
            case 'card':
                $action = 'card';
                break;
            case 'waiting':
                $waitingFor = DataRequest::whichPlayersTurnIsIt($gameId);
                $player = DataRequest::getPlayer($waitingFor);
                if (empty($player)) {
                    $player['name'] = ' trumps to be chosen';
                }
                return ['action' => 'waiting', 'for' => $player['name']];
                break;
        }
        return ['action' => $nextAction];
    }

    public static function startHandIfNeeded(Database $database, $gameId, $playerId)
    {
        $activeHand = $database->queryRow(
            "SELECT * FROM games_hands WHERE game_id = ? AND (complete = 0 OR complete IS NULL)",
            [
                $gameId
            ]
        );
        if (empty($activeHand)) {
            $lastHand = $database->queryRow(
                "SELECT * FROM games_hands WHERE game_id = ? AND (complete = 1) ORDER BY hand ASC LIMIT 1",
                [
                    $gameId
                ]
            );
            if ($lastHand['hand'] == '1') {
                //Game is complete
                DataRequest::completeGame($gameId);
            }
            if (empty($lastHand)) {
                $database->q(
                    "INSERT INTO games_hands (game_id, hand, trumps, complete) VALUE (?,?,?,?)",
                    [
                        $gameId,
                        13,
                        self::getRandomTrumps(),
                        0
                    ]
                );
                CardDrawer::dealCards($database, $gameId, 13);
                return 13;
            } else {
                $database->q(
                    "INSERT INTO games_hands (game_id, hand,  complete) VALUE (?,?,?)",
                    [
                        $gameId,
                        $lastHand['hand'] - 1,
                        0
                    ]
                );
                CardDrawer::dealCards($database, $gameId, $lastHand['hand'] - 1);
                return $lastHand['hand'] - 1;
            }
        } else {
            return $activeHand['hand'];
        }
    }

    private static function getRandomTrumps()
    {
        $suits = ['diamonds', 'spades', 'hearts', 'clubs'];
        return $suits[rand(0,3)];
    }
}