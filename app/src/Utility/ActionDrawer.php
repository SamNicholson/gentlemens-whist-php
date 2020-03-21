<?php


namespace App\Utility;


class ActionDrawer
{
    private static $handId;
    private static $playerId;
    private static $gameId;

    public static function drawActions(Database $database, $gameId, $playerId)
    {
        /**
         * Done - Submit Guess
         * Done - Submit number of wins
         * Done - Mark Hand as Complete/Deal
         * TODO Select Trumps
         * TODO Mark game as complete
         */
        $actionHTML = '';
        //Ensure that a hand is active!
        $currentHand = self::startHandIfNeeded($database, $gameId, $playerId);

        $currentHandData = $database->q(
            "SELECT * FROM games_hands_players WHERE game_id = ? AND player_id = ? AND hand = ?",
            [
                $gameId,
                $playerId,
                $currentHand
            ]
        );
        self::$handId = $currentHand;
        self::$gameId = $gameId;
        self::$playerId = $playerId;
        $actionHTML .= '<h4>Currently on Hand ' . $currentHand . '</h4>';
        $actionHTML .= self::submitGuess($currentHandData);
        $actionHTML .= self::submitWins($currentHandData);
        $actionHTML .= self::nextHand($database, $currentHand, $currentHandData);

        return $actionHTML;
    }

    private static function createFormat($valueType, $wording)
    {
        return '
            <a href="javascript:populateAndShowOverlay(
                \'' . self::$gameId . '\', 
                \'' . self::$handId . '\', 
                \'' . self::$playerId . '\', 
                \'' . '' . self::$handId . ' Card(s):<br> ' . $wording . '\', 
                \'' . $valueType . '\', 
                \'\',
            )" class="btn btn-primary">' . $wording . '</a>';
    }

    public static function nextHand($database, $currentHand, $currentHandData)
    {
        foreach ($currentHandData as $dataRow) {
            if ($dataRow['value_type'] == 'won') {

                //We do a check to ensure that all players have submitted their guesses
                $ableToMoveOn = $database->queryRow(
                    "SELECT IF(
                                   count(DISTINCT games_hands_players.player_id) = count(DISTINCT games_players.player_id),
                                   true,
                                   false
                                   ) AS result
                        FROM games_hands_players
                                 LEFT JOIN games_players ON games_hands_players.game_id = games_players.game_id
                        
                        WHERE games_players.game_id = ?
                          AND hand = ?
                          AND value_type = 'won'",
                    [
                        self::$gameId,
                        self::$handId
                    ]
                );
                if ($ableToMoveOn['result']) {
                    return self::createFormat('complete', 'Complete Round');
                } else {
                    return 'Waiting on other players';
                }

            }
        }
        return '';
    }

    public static function submitGuess($currentHandData)
    {
        foreach ($currentHandData as $dataRow) {
            if ($dataRow['value_type'] == 'guess') {
                return '';
            }
        }
        return self::createFormat('guess', 'Submit Guess');
    }

    public static function submitWins($currentHandData)
    {
        foreach ($currentHandData as $dataRow) {
            if ($dataRow['value_type'] == 'won') {
                return '';
            }
        }
        $guessFound = false;
        foreach ($currentHandData as $dataRow) {
            if ($dataRow['value_type'] == 'guess') {
                $guessFound = true;
            }
        }
        if ($guessFound) {
            return self::createFormat('won', 'Submit Wins');
        }
        return '';
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
                echo 'game ended';
                die;
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
                    "INSERT INTO games_hands (game_id, hand, trumps, complete) VALUE (?,?,?,?)",
                    [
                        $gameId,
                        $lastHand['hand'] - 1,
                        self::getRandomTrumps(),
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