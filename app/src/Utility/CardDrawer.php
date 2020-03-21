<?php

namespace App\Utility;

class CardDrawer
{
    public static function drawCards(Database $database, $gameId, $userId)
    {
        $hand = ActionDrawer::startHandIfNeeded($database, $gameId, $userId);
        $cards = $database->queryRow(
            "SELECT * FROM games_hands_cards WHERE game_id = ? AND hand = ? AND player_id = ?",
            [
                $gameId, $hand, $userId
            ]
        );
        $cardHtml = '<ul class="">';
        $cards = explode(',', $cards['cards']);
        sort($cards);
        foreach ($cards as $card) {
            $cardHtml .= self::drawCard($card);
        }
        $cardHtml .= '</ul>';
        return $cardHtml;
    }

    public static function dealCards(Database $database, $gameId, $hand)
    {
        $cardString = '';
        $cards = range(1, 52);
        $players = $database->q(
            "SELECT *, games_players.nickname FROM players 
                    LEFT JOIN games_players ON players.id = games_players.player_id
                    WHERE game_id = ?",
            [
                $gameId
            ]
        );
        foreach ($players as $player) {
            $thisPlayersCards = [];
            for ($cardNumber = 0; $cardNumber < $hand; $cardNumber++) {
                $key = array_rand($cards);
                $thisPlayersCards[] = $cards[$key];
                unset($cards[$key]);
            }
            $playerCardString = implode($thisPlayersCards, ',');
            $database->q(
                "INSERT INTO games_hands_cards (game_id, hand, player_id, cards) VALUES (?,?,?,?)",
                [
                    $gameId,
                    $hand,
                    $player['id'],
                    $playerCardString
                ]
            );
        }
    }



    public static function drawCard($number)
    {
        switch ($number) {
            case 1:
                return '<li class="card diams rank-a">
                            <span class="rank">A</span>
                            <span class="suit">&diams;</span>
                        </li>';
                break;
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 10:
            return '<li class="card diams rank-' . $number . '">
                            <span class="rank">' . $number . '</span>
                            <span class="suit">&diams;</span>
                        </li>';
                break;
            case 11:
                return '<li class="card diams rank-j">
                            <span class="rank">J</span>
                            <span class="suit">&diams;</span>
                        </li>';
            case 12:
                return '<li class="card diams rank-q">
                            <span class="rank">Q</span>
                            <span class="suit">&diams;</span>
                        </li>';
            case 13:
                return '<li class="card diams rank-k">
                            <span class="rank">K</span>
                            <span class="suit">&diams;</span>
                        </li>';
                break;

            case 14:
                return '<li class="card hearts rank-a">
                            <span class="rank">A</span>
                            <span class="suit">&hearts;</span>
                        </li>';
                break;
            case 15:
            case 16:
            case 17:
            case 18:
            case 19:
            case 20:
            case 21:
            case 22:
            case 23:
            return '<li class="card hearts rank-' . ($number-13) . '">
                            <span class="rank">' . ($number-13) . '</span>
                            <span class="suit">&hearts;</span>
                        </li>';
                break;
            case 24:
                return '<li class="card hearts rank-j">
                            <span class="rank">J</span>
                            <span class="suit">&hearts;</span>
                        </li>';
            case 25:
                return '<li class="card hearts rank-q">
                            <span class="rank">Q</span>
                            <span class="suit">&hearts;</span>
                        </li>';
            case 26:
                return '<li class="card hearts rank-k">
                            <span class="rank">K</span>
                            <span class="suit">&hearts;</span>
                        </li>';

                break;


            case 27:
                return '<li class="card spades rank-a">
                            <span class="rank">A</span>
                            <span class="suit">&spades;</span>
                        </li>';
                break;
            case 28:
            case 29:
            case 30:
            case 31:
            case 32:
            case 33:
            case 34:
            case 35:
            case 36:
            return '<li class="card spades rank-' . ($number-26) . '">
                            <span class="rank">' . ($number-26) . '</span>
                            <span class="suit">&spades;</span>
                        </li>';
                break;
            case 37:
                return '<li class="card spades rank-j">
                            <span class="rank">J</span>
                            <span class="suit">&spades;</span>
                        </li>';
                break;
            case 38:
                return '<li class="card spades rank-q">
                            <span class="rank">Q</span>
                            <span class="suit">&spades;</span>
                        </li>';
                break;
            case 39:
                return '<li class="card spades rank-k">
                            <span class="rank">K</span>
                            <span class="suit">&spades;</span>
                        </li>';
                break;
            case 40:
                return '<li class="card clubs rank-a">
                            <span class="rank">A</span>
                            <span class="suit">&clubs;</span>
                        </li>';
                break;
            case 41:
            case 42:
            case 43:
            case 44:
            case 45:
            case 46:
            case 47:
            case 48:
            case 49:
            return '<li class="card clubs rank-' . ($number-39) . '">
                            <span class="rank">' . ($number-39) . '</span>
                            <span class="suit">&clubs;</span>
                        </li>';
                break;
            case 50:
                return '<li class="card clubs rank-j">
                            <span class="rank">J</span>
                            <span class="suit">&clubs;</span>
                        </li>';
            case 51:
                return '<li class="card clubs rank-q">
                            <span class="rank">Q</span>
                            <span class="suit">&clubs;</span>
                        </li>';
            case 52:
                return '<li class="card clubs rank-k">
                            <span class="rank">K</span>
                            <span class="suit">&clubs;</span>
                        </li>';

                break;
        }

        return '<li class="card diams rank-2">
                            <span class="rank">2</span>
                            <span class="suit">&diams;</span>
                        </li>';
    }
}