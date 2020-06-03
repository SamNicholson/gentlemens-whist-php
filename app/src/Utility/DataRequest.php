<?php


namespace App\Utility;


class DataRequest
{
    /**
     * @var Database
     */
    private static $database;

    /**
     * @param Database $database
     */
    public static function setDatabase(Database $database)
    {
        self::$database = $database;
    }

    public static function completeGame($gameId)
    {
        self::$database->q(
            "UPDATE games SET completed = 1 WHERE id = ?",
            [
                $gameId
            ]
        );
        //TODO update the scores table!
        //        $hands = [13,12,11,10,9,8,7,6,5,4,3,2,1];
//        $players = DataRequest::getPlayersInGame($gameId);
//        foreach ($hands as $hand) {
//            foreach ($players as $player) {
//                $guess = isset(self::$guesses[$hand][$player['id']]) ? self::$guesses[$hand][$player['id']] : null;
//                $won = isset(self::$wins[$hand][$player['id']]) ? self::$wins[$hand][$player['id']] : null;
//                if (!isset(self::$scores[$player['id']])) {
//                    self::$scores[$player['id']] = 0;
//                }
//                $score = ($won === $guess && !is_null($won) ? $won + 10 : $won);
//                self::$scores[$player['id']] += $score;
//                return '
//            <td>' . $guess . '</td>
//            <td>' . ($won === $guess && !is_null($won) ? $won + 10 : $won) . '</td>
//        ';
//            }
//        }
    }

    public static function getAllPlayers()
    {
        return self::$database->q(
            "SELECT * FROM players"
        );
    }

    public static function getActivePlayer()
    {
        return self::$database->queryRow(
            "SELECT * FROM players WHERE id = ?",
            [
                $_SESSION['user']
            ]
        );
    }

    public static function getPlayer($playerId)
    {
        return self::$database->queryRow(
            "SELECT * FROM players WHERE id = ?",
            [
                $playerId
            ]
        );
    }

    public static function getPlayersInGame($gameId)
    {
        return self::$database->q(
            "SELECT *, games_players.nickname FROM players 
                    LEFT JOIN games_players ON players.id = games_players.player_id
                    WHERE game_id = ?
                    ORDER BY `order` ASC",
            [
                $gameId
            ]
        );
    }

    public static function getHandDataForPlayer($gameId, $playerId, $hand)
    {
        return self::$database->q(
            "SELECT * FROM games_hands_players WHERE game_id = ? AND player_id = ? AND hand = ?",
            [
                $gameId,
                $playerId,
                $hand
            ]
        );
    }

    public static function getHandData($gameId, $hand)
    {
        return self::$database->queryRow(
            "SELECT * FROM games_hands WHERE game_id = ? AND hand = ?",
            [
                $gameId,
                $hand
            ]
        );
    }

    public static function getCompleteGames()
    {
        return self::$database->q(
            "SELECT games.id, games.name, GROUP_CONCAT(DISTINCT games_players.nickname ORDER BY games_players.player_id SEPARATOR ', ') AS playerList, games.start_time FROM games
                    LEFT JOIN games_players ON games.id = games_players.game_id
                    LEFT JOIN players ON players.id = games_players.player_id 
                    WHERE completed = 1
                    GROUP BY games.id
                    ORDER BY games.id DESC
                    "
        );
    }

    public static function getNonCompleteGames()
    {
        return self::$database->q(
            "SELECT games.id, games.name, GROUP_CONCAT(DISTINCT games_players.nickname ORDER BY games_players.player_id SEPARATOR ', ') AS playerList, games.start_time FROM games
                    LEFT JOIN games_players ON games.id = games_players.game_id
                    LEFT JOIN players ON players.id = games_players.player_id 
                    WHERE (completed = 0 OR completed IS NULL)
                    GROUP BY games.id
                    ORDER BY games.id DESC
                    "
        );
    }

    public static function getTurnsForHand($gameId, $hand)
    {
        return self::$database->q(
            "SELECT * FROM games_hands_turns WHERE game_id = ? AND hand = ? ORDER BY turn DESC",
            [$gameId, $hand]
        );
    }

    public static function getGameInfo($gameId)
    {
        return self::$database->queryRow("SELECT * FROM games WHERE id = ?",[$gameId]);
    }

    public static function getCardsInTurn($gameId, $hand, $turn)
    {
        return self::$database->q(
            "SELECT * FROM games_hands_turns WHERE game_id = ? AND hand = ? AND turn = ?",
            [
                $gameId,
                $hand,
                $turn
            ]
        );
    }

    public static function getCardsPlayedInHand($gameId, $hand, $playerId)
    {
        $cardRows = self::$database->q(
            "SELECT * FROM games_hands_turns WHERE game_id = ? AND hand = ? AND player_id = ?",
            [
                $gameId,
                $hand,
                $playerId
            ]
        );
        $return = [];
        foreach ($cardRows as $row) {
            $return[$row['turn']] = $row['card'];
        }
        return $return;
    }

    public static function getCardsPlayedInTurn($gameId, $hand, $turn)
    {
        $cardRows = self::$database->q(
            "SELECT * FROM games_hands_turns WHERE game_id = ? AND hand = ? AND turn = ?",
            [
                $gameId,
                $hand,
                $turn
            ]
        );
        $return = [];
        foreach ($cardRows as $row) {
            $return[$row['turn']] = $row['card'];
        }
        return $return;
    }

    public static function getCardsPlayedInTurnByPlayer($gameId, $hand, $turn)
    {
        $cardRows = self::$database->q(
            "SELECT * FROM games_hands_turns WHERE game_id = ? AND hand = ? AND turn = ?",
            [
                $gameId,
                $hand,
                $turn
            ]
        );
        $return = [];
        foreach ($cardRows as $row) {
            $return[$row['player_id']] = $row['card'];
        }
        return $return;
    }

    public static function getCardsInHand($gameId, $hand)
    {
        $cardsPlayed = self::$database->q(
            "SELECT * FROM games_hands_turns WHERE game_id = ? AND hand = ? ORDER BY turn ASC",
            [
                $gameId,
                $hand
            ]
        );
        $return = [];
        foreach ($cardsPlayed as $row) {
            if (!isset($return[$row['player_id']])) {
                $return[$row['player_id']] = [];
            }
            $return[$row['player_id']][$row['turn']] = $row['card'];
        }
        return $return;
    }

    public static function whichTurnIsIt($gameId, $hand)
    {
        $turns = self::getTurnsForHand($gameId, $hand);
        if (empty($turns)) {
            $potentialTurn = 1;
        } else {
            $potentialTurn = $turns[0]['turn'];
        }
        //Have all players laid their cards - if so - we tell tell the game its the next turns
        $cardsLaid = self::getCardsInTurn($gameId, $hand, $potentialTurn);
        $players = DataRequest::getPlayersInGame($gameId);
        if (empty($cardsLaid)) {
            $guesses = GameDrawer::getGuesses(self::$database, $gameId);
            if (!isset($guesses[$hand])) {
                return 0;
            }
            if (count($guesses[$hand]) == count($players)) {
                //We've got all the guesses - first players turn is the one with the lowest order
                return $potentialTurn;
            }
            return 0;
        }
        if (count($cardsLaid) == count($players)) {
            if ($potentialTurn + 1 > $hand) {
                self::$database->q(
                    "UPDATE games_hands SET complete = 1 WHERE game_id = ? AND hand = ?",
                    [
                        $gameId,
                        $hand
                    ]
                );
                self::scoreHand($gameId, $hand);
                return 0;
            }
            return $potentialTurn + 1;
        }
        return $potentialTurn;
    }

    /**
     * Returns 0 if its not a players turn to lay a card
     * OR returns
     *
     * @param $gameId
     * @return int|mixed
     */
    public static function whichPlayersTurnIsIt($gameId)
    {
        $currentHand = ActionDrawer::startHandIfNeeded(self::$database, $gameId, $_SESSION['user']);
        self::scoreHand($gameId, $currentHand);
        $game = self::getGameInfo($gameId);
        $players = DataRequest::getPlayersInGame($gameId);
        $currentTurn  = self::whichTurnIsIt($gameId, $currentHand);
        $cardsLaid = self::getCardsInTurn($gameId, $currentHand, $currentTurn);
        //Handling for first turn of game OR we moved to a new hand!
        if ($currentTurn == 0) {
            //We need to tell the game who should be guessing!
            return self::whichPlayersGuessIsIt($gameId, $currentHand);
        }
        if ($currentTurn == 1 && empty($cardsLaid) && $currentHand == 13) {
            return $players[0]['id'];
        } else if ($currentTurn == 1 && $currentHand == 13) {
            foreach ($players as $player) {
                $playerHasLaidCard = false;
                foreach ($cardsLaid as $card) {
                    if ($card['player_id'] == $player['id']) {
                        $playerHasLaidCard = true;
                    }
                }
                if (!$playerHasLaidCard) {
                    return $player['id'];
                }
            }
            return 0;
        } else if (empty($cardsLaid) && $currentTurn == 1) {
            return self::whoWonHand($gameId, $currentHand + 1);
        } else if (!empty($cardsLaid)) {
            $cardsPlayed = self::getCardsPlayedInTurnByPlayer($gameId, $currentHand, $currentTurn);
            $potentialPlayer = self::getPlayerAfter($gameId, $cardsLaid[count($cardsLaid)-1]['player_id']);
            if (isset($cardsPlayed[$potentialPlayer])) {
                $potentialPlayer = self::getPlayerAfter($gameId, $potentialPlayer);
            }
            return $potentialPlayer;
        }
        if ($currentHand == 13) {
            $turnWinners = self::calculateTurnWinners($gameId, $currentHand, $players[0]['id']);
            return $turnWinners[$currentTurn - 1];
        }
        if ($currentTurn == 1) {
            return self::whoWonHand($gameId, $currentHand + 1);
        }
        $lastHandWinner = self::whoWonHand($gameId, $currentHand + 1);
        $turnWinners = self::calculateTurnWinners($gameId, $currentHand, $lastHandWinner);
        return $turnWinners[$currentTurn - 1];
    }

    public static function whichPlayersGuessIsIt($gameId, $hand)
    {
        $rows = self::$database->q(
            "SELECT * FROM games_hands_players WHERE game_id = ? AND hand = ? AND value_type = 'guess'",
            [
                $gameId,
                $hand
            ]
        );
        $players = self::getPlayersInGame($gameId);
        $guesses = [];
        foreach ($rows as $row) {
            $guesses[$row['player_id']] = $row['value'];
        }
        if ($hand == 13) {
            $shouldGuess = $players[0]['id'];
        } else {
            $shouldGuess = self::whoWonHand($gameId, $hand + 1);
        }
        for ($i = 0; $i < count($players); $i++) {
            if (!isset($guesses[$shouldGuess])) {
                return $shouldGuess;
            } else {
                $shouldGuess = self::getPlayerAfter($gameId, $shouldGuess);
            }
        }
    }

    public static function getPlayerAfter($gameId, $playerId)
    {
        $players = self::getPlayersInGame($gameId);
        $nextPlayer = true;
        $turnOf = 0;
        foreach ($players as $player) {
            if ($nextPlayer) {
                $turnOf = $player['id'];
            }
            if ($player['id'] == $playerId) {
                $nextPlayer = true;
            } else {
                $nextPlayer = false;
            }
        }
        if ($nextPlayer) {
            $turnOf = $players[0]['id'];
        }
        return $turnOf;
    }

    public static function scoreHand($gameId, $hand)
    {
        $players = DataRequest::getPlayersInGame($gameId);
        $handData = DataRequest::getHandData($gameId, $hand);
        $playerScores = [];
        foreach ($players as $player) {
            $playerScores[$player['id']] = 0;
        }

        $startingPlayerForHand = 0;
        if ($hand == 13) {
            //Its the first player
            $startingPlayer = $players[0]['id'];
        } else {
            //Its the player that won the last hand!
            $startingPlayer = self::whoWonHand($gameId, $hand + 1);
        }

        $winners = self::calculateTurnWinners($gameId, $hand, $startingPlayer);

        foreach ($winners as $winner) {
            if ($winner) {
                $playerScores[$winner]++;
            }
        }

        foreach ($players as $player) {
            self::$database->q(
                "INSERT INTO games_hands_players (game_id, hand, player_id, value_type, value) VALUES (?,?,?,'won',?) ON DUPLICATE KEY UPDATE value = ?",
                [
                    $gameId,
                    $hand,
                    $player['id'],
                    $playerScores[$player['id']],
                    $playerScores[$player['id']]
                ]
            );
        }
    }

    public static function getLeadingSuitForTurn($gameId, $hand, $turn)
    {
        if ($hand == 13) {
            $players = DataRequest::getPlayersInGame($gameId);
            $turnWinners = DataRequest::calculateTurnWinners($gameId, $hand, $players[0]['id']);
        } else {
            $turnWinners = DataRequest::calculateTurnWinners($gameId, $hand, DataRequest::whoWonHand($gameId, $hand + 1));
        }
        $cards = self::getCardsPlayedInTurnByPlayer($gameId, $hand, $turn);
        if (empty($cards)) {
            return '';
        }
        if (isset($turnWinners[$turn - 1])) {
            return CardDrawer::whatSuitIsCard($cards[$turnWinners[$turn - 1]]);
        }
    }

    public static function calculateTurnWinners($gameId, $hand, $firstTurnStarter)
    {
        $winners = [];
        $turnsData = DataRequest::getTurnsForHand($gameId, $hand);
        $players = DataRequest::getPlayersInGame($gameId);
        $handData = DataRequest::getHandData($gameId, $hand);

        $cardsData = [];
        foreach ($turnsData as $dataRow) {
            if (!isset($turnsData[$dataRow['turn']])) {
                $turnsData[$dataRow['turn']] = [];
            }
            $cardsData[$dataRow['turn']][$dataRow['player_id']] = $dataRow['card'];
        }

        $lastRoundWinner = $firstTurnStarter;
        for ($i = 1; $i <= $hand; $i++) {
            $winingPlayer = 0;
            if (!empty($cardsData[$i])) {
                if (!empty($cardsData[$i][$lastRoundWinner])) {
                    $startingSuit = CardDrawer::whatSuitIsCard($cardsData[$i][$lastRoundWinner]);
                    $winningCard  = CardDrawer::whatIsTheWinningCard($startingSuit, $handData['trumps'], $cardsData[$i]);
                    foreach ($players as $player) {
                        if (isset($cardsData[$i][$player['id']])) {
                            if ($cardsData[$i][$player['id']] == $winningCard) {
                                $winingPlayer = $player['id'];
                            }
                        }
                    }
                }
            }
            $winners[$i] = $winingPlayer;
            $lastRoundWinner = $winingPlayer;
        }
        return $winners;
    }

    public static function whatNumberCannotBeGuessed($gameId, $hand)
    {
        $players = self::getPlayersInGame($gameId);
        $rows =  self::$database->q(
            "SELECT * FROM games_hands_players WHERE game_id = ? AND hand = ? AND (value_type = 'guess')",
            [
                $gameId,
                $hand
            ]
        );
        if (count($players) == (count($rows) + 1)) {
            $total = 0;
            foreach ($rows as $row) {
                $total += $row['value'];
            }
            return $hand - $total;
        }
        return 1000;
    }

    public static function whoWonHand($gameId, $hand)
    {
        $winsAndGuess = self::$database->q(
            "SELECT * FROM games_hands_players WHERE game_id = ? AND hand = ? AND (value_type = 'guess' OR value_type = 'won')",
            [
                $gameId,
                $hand
            ]
        );
        $players = DataRequest::getPlayersInGame($gameId);

        $guesses = [];
        $wins = [];
        foreach ($winsAndGuess as $winOrGuess) {
            if ($winOrGuess['value_type'] == 'guess') {
                $guesses[$winOrGuess['player_id']] = $winOrGuess['value'];
            } else {
                $wins[$winOrGuess['player_id']] = $winOrGuess['value'];
            }
        }
        $scores = [];
        foreach ($players as $player) {
            if ($guesses[$player['player_id']] == $wins[$player['player_id']] ) {
                $scores[$player['player_id']] = $wins[$player['player_id']] + 10;
            } else {
                $scores[$player['player_id']] = $wins[$player['player_id']];
            }
        }
        $winner = 0;
        $winningScore = -1;
        foreach ($scores as $playerId => $score) {
            if ($score > $winningScore) {
                $winner = $playerId;
                $winningScore = $score;
            } else if ($score == $winningScore) {
                if ($hand == 13) {
                    $winner = $players[0];
                } else {
                    $winner = self::whoWonHandFiltered($gameId, $hand + 1, [$winner, $playerId]);
                }
                $winningScore = $score;
            }
        }
        return $winner;
    }

    public static function whoWonHandFiltered($gameId, $hand, $filteredPlayers)
    {
        $winsAndGuess = self::$database->q(
            "SELECT * FROM games_hands_players WHERE game_id = ? AND hand = ? AND (value_type = 'guess' OR value_type = 'won')",
            [
                $gameId,
                $hand
            ]
        );
        $allPlayers = DataRequest::getPlayersInGame($gameId);

        $players = [];
        foreach ($allPlayers as $player) {
            if (in_array($player['player_id'], $filteredPlayers)) {
                $players[] = $player;
            }
        }

        $guesses = [];
        $wins = [];
        foreach ($winsAndGuess as $winOrGuess) {
            if ($winOrGuess['value_type'] == 'guess') {
                $guesses[$winOrGuess['player_id']] = $winOrGuess['value'];
            } else {
                $wins[$winOrGuess['player_id']] = $winOrGuess['value'];
            }
        }
        $scores = [];
        foreach ($players as $player) {
            if ($guesses[$player['player_id']] == $wins[$player['player_id']] ) {
                $scores[$player['player_id']] = $wins[$player['player_id']] + 10;
            } else {
                $scores[$player['player_id']] = $wins[$player['player_id']];
            }
        }
        $winner = 0;
        $winningScore = -1;
        foreach ($scores as $playerId => $score) {
            if ($score > $winningScore) {
                $winner = $playerId;
                $winningScore = $score;
            } else if ($score == $winningScore) {
                if ($hand == 13) {
                    $winner = $players[0];
                } else {
                    $winner = self::whoWonHandFiltered($gameId, $hand + 1, [$winner, $playerId]);
                }
                $winningScore = $score;
            }
        }
        return $winner;
    }

}