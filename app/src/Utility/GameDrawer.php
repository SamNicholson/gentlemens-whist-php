<?php


namespace App\Utility;


class GameDrawer
{
    public static $guesses;
    public static $wins;
    public static $scores = [];

    public static function drawGame(Database $database, $gameId)
    {
        $hands = [13,12,11,10,9,8,7,6,5,4,3,2,1];

        self::getGuesses($database, $gameId);;
        self::getWins($database, $gameId);
        $players = DataRequest::getPlayersInGame($gameId);
        $gameRows = $database->q(
            "SELECT * FROM games_hands WHERE game_id = ?",
            [
                $gameId
            ]
        );
        $trumps = [];
        $completedHands = [];
        foreach ($gameRows as $row){
            $trumps[$row['hand']] = $row['trumps'];
            $completedHands[$row['hand']] = $row['complete'];
        }
        $currentPlayerTurn = DataRequest::whichPlayersTurnIsIt($gameId);
        $html = "";
        $html  .= '<div class="row">
                    <div class="col-md-6">';
        self::drawScoreTable($database, $gameId, $players, $currentPlayerTurn, $completedHands, $hands, $trumps, $html);
        $html .= '</div>';
        self::drawTurnsTable($database, $gameId, $players, $trumps, $currentPlayerTurn, $html);
        $html .= '</div>';
        return $html;
    }

    public static function drawAllCards()
    {
        $html = "";
        for ($i = 53; $i > 0; $i--) {
            $html .= CardDrawer::drawCard($i);
        }
        return $html;
    }

    public static function getCompletedTable(Database $database, $gameId, $players, $trumps, $currentPlayerTurn, &$html)
    {
        $html .= '<div class="col-md-6">
            <h4>Final Scores</h4>';

        $scoredPlayers = [];
        foreach ($players as $player) {
            $scoredPlayers[self::$scores[$player['id']]] = $player;
        }
        krsort($scoredPlayers);

        $cardsPlayed = DataRequest::getCardsInHand($gameId, 1);

        $html .= '<table class="table game-table table-striped">
                <thead>
                    <tr>
                        <td style="font-weight:bold;">Position</td>
                        <td style="font-weight:bold;">Player</td>
                        <td style="font-weight:bold;">Score</td>
                    </tr>
                </thead>
            ';
        $count = 1;
        foreach ($scoredPlayers as $score => $player) {
            $position = '1st Place';
            switch ($count) {
                case 2:
                    $position = '2nd Place';
                    break;
                case 3:
                    $position = '3rd Place';
                    break;
                case 4:
                    $position = '4th Place';
                    break;
            }
            $html .= '<tr>';
            $html .= '<td>' . $position . '</td>';
            $html .= '<td>' . $player['nickname'] . ' <br><i>' . $player['name'] . '</td>';
            $html .= '<td>' . $score . '</td>';
            $html .= '</tr>';
            $count++;
        }
        $html .= '</table><br><br>';
        $html .= '<h4>1 Card Draw</h4>';
        $html .= '<table class="table game-table table-striped">
            <tr>';
        foreach ($players as $player) {
            $html .= '<td>' . $player['nickname'] . ' <br><i>' . $player['name'] . '</td>';
        }
        $html .= '</tr><tr>';
        foreach ($players as $player) {
            $html .= '<td class="text-center">' . CardDrawer::drawCard($cardsPlayed[$player['id']][1]) . '</td>';
        }
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';
    }

    public static function drawTurnsTable(Database $database, $gameId, $players, $trumps, $currentPlayerTurn, &$html)
    {
        $currentHand = ActionDrawer::startHandIfNeeded($database, $gameId, $_SESSION['user']);
        $currentTurn = DataRequest::whichTurnIsIt($gameId, $currentHand);
        $cardsPlayed = DataRequest::getCardsInHand($gameId, $currentHand);

        $completed = DataRequest::isGameComplete($gameId);
        if ($completed) {
           self::getCompletedTable($database, $gameId, $players, $trumps, $currentPlayerTurn, $html);
           return;
        }

        $html .= '<div class="col-md-6">
            <h4>' . $currentHand . ' Card Draw</h4>
            <h4>Turn ' . $currentTurn . '</h4>';
        $html .= '<br><table class="table game-table table-striped"><head>';
        $contentHTML = '';

        $html .= '<tr>';
        $html .= '<th></th>';
        foreach ($players as $player) {
            $thisPlayersTurn = $player['id'] == $currentPlayerTurn;
            $playerColor = $thisPlayersTurn ? 'success' : '';
            $html .= '<th class="' . $playerColor . '">' . $player['nickname'] . ' <br><i>' . $player['name'] . ($thisPlayersTurn ? '<br><i>(Turn)</i>' : '') . '</th>';
        }
        $html .= '</tr>';

        for ($i = $currentHand; $i >= 1; $i--) {
            $cardPlayed = false;
            foreach ($players as $player) {
                if (isset($cardsPlayed[$player['id']][$i])) {
                    $cardPlayed = true;
                }
            }
            //Content HTML
            if ($cardPlayed) {
                $turnColor = $currentTurn == $i ? 'warning' : '';
                $contentHTML .= '<tr>';
                $contentHTML .= '<td class="' . $turnColor . '">' . $i;
                foreach ($players as $player) {
                    if (isset($cardsPlayed[$player['id']][$i])) {
                        $contentHTML .= '<td class="' . $turnColor . '">' . CardDrawer::drawCard($cardsPlayed[$player['id']][$i]) . '</td>';
                    } else {
                        $contentHTML .= '<td class="' . $turnColor . '"></td>';
                    }
                }
                $contentHTML .= '</tr>';
            }
        }
        $html .= '</head>';
        $html .= '<tbody>' . $contentHTML . '</tbody>';

        $html .= '</table>';
        $html .= '</div>';
    }

    public static function drawScoreTable(Database $database, $gameId, $players, $currentPlayerTurn, $completedHands, $hands, $trumps, &$html)
    {
        $html .= '<table class="table table-striped table-bordered game-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Hand</th>
                            <th rowspan="2">Trumps</th>
                                
                        ';
        $secondHTML = '<tr>';
        foreach ($players as $player) {
            $thisPlayersTurn = $player['id'] == $currentPlayerTurn;
            $playerColor = $thisPlayersTurn ? 'success' : '';
            $html .= '<th colspan="2" class="' . $playerColor . '">' . $player['nickname'] . ' <br><i>' . $player['name'] . ($thisPlayersTurn ? '<br><i>(Turn)</i>' : '') . '</th>';
            $secondHTML .= '<th>Guess</th><th>Score</th>';
        }
        $secondHTML .= '</tr>';

        $html .= '</tr>' . $secondHTML . '</thead><tbody>';

        foreach ($hands as $hand) {
            if (isset($completedHands[$hand])) {
                $color = $completedHands[$hand] ? 'success' : 'warning';
            } else {
                $color = '';
            }
            $trumpColor = '';
            if (isset($trumps[$hand])) {
                if ($trumps[$hand] == 'diamonds' || $trumps[$hand] == 'hearts') {
                    $trumpColor = 'color:red;';
                }
            }
            $html .= '
                <tr class="' . $color . '">
                    <td>'  . $hand . '</td>
                    <td><span style="font-size:20pt;' . $trumpColor . '">'  . (isset($trumps[$hand]) ? CardDrawer::suitToHTML($trumps[$hand]) : '' ). '</span></td>
            ';
            foreach ($players as $player) {
                $html .= self::drawPlayerHandBox($player, $hand);
            }
            $html .= '</tr>';
        }

        $game = $database->queryRow("SELECT * FROM games WHERE id = ?", [$gameId]);
        $html .= '<tr><td colspan="2">Total Score</td>';
        if ($game['completed']) {
            foreach ($players as $player) {
                $html .= '<td colspan="2">' . self::$scores[$player['id']] . '</td>';
            }
            $html .= '</tr>';
        } else {
            $html .= '<td colspan="' . (count($players) * 2) . '" class="text-center">Game Not Complete</td>';
        }

        $html .= '</tbody></table>';
    }

    public static function getGuesses(Database $database, $gameId)
    {
        $rows = $database->q("SELECT * FROM games_hands_players WHERE game_id = ? AND value_type = 'guess'", [$gameId]);
        $guesses = [];
        foreach ($rows as $row) {
            if (!isset($guesses[$row['hand']])) {
                $guesses[$row['hand']] = [];
            }
            if (!isset($guesses[$row['hand']][$row['player_id']])) {
                $guesses[$row['hand']][$row['player_id']] = [];
            }
            $guesses[$row['hand']][$row['player_id']] = $row['value'];
        }
        self::$guesses = $guesses;
        return $guesses;
    }

    private static function drawPlayerHandBox($player, $hand)
    {
        $guess = isset(self::$guesses[$hand][$player['id']]) ? self::$guesses[$hand][$player['id']] : null;
        $won = isset(self::$wins[$hand][$player['id']]) ? self::$wins[$hand][$player['id']] : null;
        if (!isset(self::$scores[$player['id']])) {
            self::$scores[$player['id']] = 0;
        }
        $score = ($won === $guess && !is_null($won) ? $won + 10 : $won);
        self::$scores[$player['id']] += $score;
        return '
            <td>' . $guess . '</td>
            <td>' . ($won === $guess && !is_null($won) ? $won + 10 : $won) . '</td>
        ';
    }

    private static function getWins(Database $database, $gameId)
    {
        $rows = $database->q("SELECT * FROM games_hands_players WHERE game_id = ? AND value_type = 'won'", [$gameId]);
        $wins = [];
        foreach ($rows as $row) {
            if (!isset($wins[$row['hand']])) {
                $wins[$row['hand']] = [];
            }
            if (!isset($wins[$row['hand']][$row['player_id']])) {
                $wins[$row['hand']][$row['player_id']] = [];
            }
            $wins[$row['hand']][$row['player_id']] = $row['value'];
        }
        self::$wins = $wins;
    }
}