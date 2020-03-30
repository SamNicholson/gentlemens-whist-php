<?php


namespace App\Utility;


class GameDrawer
{
    private static $guesses;
    private static $wins;
    private static $scores = [];

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
        $html  = '<div class="row">
                    <div class="col-md-6">';
        self::drawScoreTable($database, $gameId, $players, $currentPlayerTurn, $completedHands, $hands, $trumps, $html);
        $html .= '</div>';
        self::drawTurnsTable($database, $gameId, $players, $trumps, $currentPlayerTurn, $html);
        $html .= '</div>';
        return $html;
    }

    public static function drawTurnsTable(Database $database, $gameId, $players, $trumps, $currentPlayerTurn, &$html)
    {
        $currentHand = ActionDrawer::startHandIfNeeded($database, $gameId, $_SESSION['user']);
        $currentTurn = DataRequest::whichTurnIsIt($gameId, $currentHand);
        $cardsPlayed = DataRequest::getCardsInHand($gameId, $currentHand);
        $html .= '<div class="col-md-6">
            <h4>' . $currentHand . ' Card Draw</h4>
            <h4>Turn ' . $currentTurn . '</h4>';
        $html .= '<table class="table game-table table-striped"><head>';
        $contentHTML = '';

        $html .= '<tr>';
        $html .= '<th></th>';
        foreach ($players as $player) {
            $thisPlayersTurn = $player['id'] == $currentPlayerTurn;
            $playerColor = $thisPlayersTurn ? 'success' : '';
            $html .= '<th class="' . $playerColor . '">' . $player['nickname'] . ' <br><i>' . $player['name'] . ($thisPlayersTurn ? '<br><i>(Turn)</i>' : '') . '</th>';
        }
        $html .= '</tr>';


        for ($i = 1; $i <= $currentHand; $i++) {
            //Content HTML
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