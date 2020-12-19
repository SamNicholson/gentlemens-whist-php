<?php

namespace App\Utility;


class CardDrawer
{
    public static function drawCards(Database $database, $gameId, $userId)
    {
        $hand = ActionDrawer::startHandIfNeeded($database, $gameId, $userId);
        $currentTurn = DataRequest::whichTurnIsIt($gameId, $hand);

        $cards = $database->queryRow(
            "SELECT * FROM games_hands_cards WHERE game_id = ? AND hand = ? AND player_id = ?",
            [
                $gameId, $hand, $userId
            ]
        );
        $nextAction = GameState::whatIsMyNextAction($database, $gameId);
        if ($nextAction == 'card') {

        }
        $usedCards = DataRequest::getCardsPlayedInHand($gameId, $hand, $_SESSION['user']);
        $cardsInTurn = DataRequest::getCardsPlayedInTurnByPlayer($gameId, $hand, $currentTurn);

        $cards = explode(',', $cards['cards']);

        $suitsInHand = [];
        foreach ($cards as $card) {
            if (!in_array($card, $usedCards)) {
                $suitsInHand[self::whatSuitIsCard($card)] = true;
            }
        }
        rsort($cards);
        $cardsData = [];
        foreach ($cards as $card) {
            if (!in_array($card, $usedCards)) {
                if ($nextAction == 'card') {
                    $leadingSuit = DataRequest::getLeadingSuitForTurn($gameId, $hand, $currentTurn);
                    if (empty($leadingSuit)) {
                        $cardsData[$card] = [
                            'playable-card' => true,
                            'raised'        => false,
                            'disabled'      => false
                        ];
                    } else {
                        if (!isset($suitsInHand[$leadingSuit])) {
                            $cardsData[$card] = [
                                'playable-card' => true,
                                'raised'        => true,
                                'disabled'      => false
                            ];
                        } elseif ($leadingSuit == self::whatSuitIsCard($card)) {
                            $cardsData[$card] = [
                                'playable-card' => true,
                                'raised'        => true,
                                'disabled'      => false
                            ];
                        } else {
                            $cardsData[$card] = [
                                'playable-card' => false,
                                'raised'        => false,
                                'disabled'      => true
                            ];
                        }
                    }
                } else {
                    $cardsData[$card] = [
                        'playable-card' => false,
                        'raised'        => false,
                        'disabled'      => true
                    ];
                }
            }
        };
        return $cardsData;
    }

    public static function dealCards(Database $database, $gameId, $hand)
    {
        $cardString = '';
        $cards = range(1, 52);
        $players = $database->q(
            "SELECT *, games_players.nickname FROM players 
                    LEFT JOIN games_players ON players.id = games_players.player_id
                    WHERE game_id = ? ORDER BY games_players.`order`",
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

    public static function suitToHTML($suit)
    {
        switch ($suit) {
            case 'diamonds':
                return '&diams;';
                break;
            case 'hearts':
                return '&hearts;';
                break;
            case 'spades':
                return '&spades;';
                break;
            case 'clubs':
                return '&clubs;';
                break;
        }
    }


    private static function getCardHTML($suit, $rank, $value, $playable = 'playable-card', $extraText = '')
    {
        return '<div id="card-' . $value . '" data-card="' . $value . '" class="' . $playable . ' card ' . $suit . ' rank-' . $rank . '">
                            <span class="rank">' . $rank . '</span>
                            <span class="' . $suit . '">&' . $suit . ';</span>
                            <span class="extra-text">' . $extraText . '</span>
                        </div>';
    }

    public static function drawCard($number, $playable = 'playable-card', $extraText = '')
    {
        switch ($number) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
                return self::getCardHTML('diams', $number + 1, $number, $playable, $extraText);
                break;
            case 10:
                return self::getCardHTML('diams', 'J', $number, $playable, $extraText);
            case 11:
                return self::getCardHTML('diams', 'Q', $number, $playable, $extraText);
            case 12:
                return self::getCardHTML('diams', 'K', $number, $playable, $extraText);
                break;
            case 13:
                return self::getCardHTML('diams', 'A', $number, $playable, $extraText);
                break;
            case 14:
            case 15:
            case 16:
            case 17:
            case 18:
            case 19:
            case 20:
            case 21:
            case 22:
                return self::getCardHTML('spades', $number - 12, $number, $playable, $extraText);
                break;
            case 23:
                return self::getCardHTML('spades', 'J', $number, $playable, $extraText);
            case 24:
                return self::getCardHTML('spades', 'Q', $number, $playable, $extraText);
            case 25:
                return self::getCardHTML('spades', 'K', $number, $playable, $extraText);
                break;
            case 26:
                return self::getCardHTML('spades', 'A', $number, $playable, $extraText);
                break;
            case 27:
            case 28:
            case 29:
            case 30:
            case 31:
            case 32:
            case 33:
            case 34:
            case 35:
                return self::getCardHTML('hearts', $number - 25, $number, $playable, $extraText);
                break;
            case 36:
                return self::getCardHTML('hearts', 'J', $number, $playable, $extraText);
            case 37:
                return self::getCardHTML('hearts', 'Q', $number, $playable, $extraText);
            case 38:
                return self::getCardHTML('hearts', 'K', $number, $playable, $extraText);
                break;
            case 39:
                return self::getCardHTML('hearts', 'A', $number, $playable, $extraText);
                break;
            case 40:
            case 41:
            case 42:
            case 43:
            case 44:
            case 45:
            case 46:
            case 47:
            case 48:
                return self::getCardHTML('clubs', $number - 38, $number, $playable, $extraText);
                break;
            case 49:
                return self::getCardHTML('clubs', 'J', $number, $playable, $extraText);
            case 50:
                return self::getCardHTML('clubs', 'Q', $number, $playable, $extraText);
            case 51:
                return self::getCardHTML('clubs', 'K', $number, $playable, $extraText);
                break;
            case 52:
                return self::getCardHTML('clubs', 'A', $number, $playable, $extraText);
                break;
        }

        return '';
    }

    public static function whatSuitIsCard($card)
    {
        if ($card <= 13) {
            return 'diamonds';
        }
        if ($card <= 26) {
            return 'spades';
        }
        if ($card <= 39) {
            return 'hearts';
        }
        return 'clubs';
    }

    public static function whatIsTheWinningCard($startingSuit, $trumps, array $cards)
    {
        //We look for the highest trump card to be the winner
        $highestTrump = 0;
        foreach ($cards as $card) {
            if (self::whatSuitIsCard($card) == $trumps) {
                if ($card > $highestTrump) {
                    $highestTrump = $card;
                }
            }
        }
        if ($highestTrump > 0) {
            return $highestTrump;
        }

        //We look for the highest starting suit card to be the winner
        $highestDominantSuit = 0;
        foreach ($cards as $card) {
            if (self::whatSuitIsCard($card) == $startingSuit) {
                if ($card > $highestDominantSuit) {
                    $highestDominantSuit = $card;
                }
            }
        }
        if ($highestDominantSuit > 0) {
            return $highestDominantSuit;
        }
    }
}