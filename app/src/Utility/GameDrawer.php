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
        $players = $database->q(
            "SELECT *, games_players.nickname FROM players 
                    LEFT JOIN games_players ON players.id = games_players.player_id
                    WHERE game_id = ?",
            [
                $gameId
            ]
        );
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

        $html = '<table class="table table-striped table-bordered ">
                    <thead>
                        <tr>
                            <th rowspan="2">Hand</th>
                            <th rowspan="2">Trumps</th>
                                
                        ';
        $secondHTML = '<tr>';
        foreach ($players as $player) {
            $html .= '<th colspan="2">' . $player['nickname'] . ' <br><i>' . $player['name'] . '</i></th>';
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
            $html .= '
                <tr class="' . $color . '">
                    <td>'  . $hand . '</td>
                    <td>'  . (isset($trumps[$hand]) ? $trumps[$hand] : '' ). '</td>
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

        return $html . '</tbody></<table>';
    }

    private static function getGuesses(Database $database, $gameId)
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