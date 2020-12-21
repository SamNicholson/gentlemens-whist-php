<?php

namespace App\Utility;

class GameState
{
    public static function whatIsMyNextAction( Database $database, $gameId)
    {
        $player = DataRequest::getActivePlayer();
        $hand = ActionDrawer::startHandIfNeeded($database, $gameId, $player['id']);
        $turn = DataRequest::whichTurnIsIt($gameId, $hand);
        $players = DataRequest::getPlayersInGame($gameId);

        $completed = DataRequest::isGameComplete($gameId);

        if ($completed) {
            return 'completed';
        }

        $handData = DataRequest::getHandData($gameId, $hand);
        //Choose Trumps
        if (empty($handData['trumps'])) {
            //Am I the one to choose trumps?
            if ($hand == 13) {
                $trumpChooser = $players[0];
            } else {
                $trumpChooser = DataRequest::whoWonHand($gameId, $hand + 1);
            }
            if ($trumpChooser == $player['id']) {
                return 'trumps';
            } else {
                return 'waiting';
            }
        }

        //Submit number of guesses
        $playerHandData = DataRequest::getHandDataForPlayer($gameId, $player['id'], $hand);

        //Is it my turn?
        $turnForPlay = DataRequest::whichPlayersTurnIsIt($gameId);

        if ($turnForPlay == $player['id']) {
            $guessMade = false;
            foreach ($playerHandData as $dataRow) {
                if ($dataRow['value_type'] == 'guess') {
                    $guessMade = true;
                }
            }
            if (!$guessMade) {
                return 'guess';
            }

            //Play a card
            return 'card';
        } else {
            return 'waiting';
        }
    }
}