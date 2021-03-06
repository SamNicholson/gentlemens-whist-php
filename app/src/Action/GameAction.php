<?php


namespace App\Action;


use App\Helpers;
use App\Utility\ActionDrawer;
use App\Utility\CardDrawer;
use App\Utility\Database;
use App\Utility\DataRequest;
use App\Utility\GameDrawer;
use Psr\Log\LoggerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;

class GameAction
{
    /**
     * @var Twig
     */
    private $view;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Database
     */
    private $database;

    public function __construct(Twig $view, LoggerInterface $logger, Database $database)
    {
        $this->view = $view;
        $this->logger = $logger;
        $this->database = $database;
    }

    public function game(Request $request, Response $response, $args)
    {
        $game = $this->database->queryRow("SELECT * FROM games WHERE id = ?", [$args['gameId']]);
        $this->view->render($response, 'game.twig',
            [
                'game'     => $game,
                'gameHTML' => GameDrawer::drawGame($this->database, $args['gameId']),
                'cardHTML' => GameDrawer::drawAllCards(),
                'events'   => DataRequest::getAllEventsSince($args['gameId'])
            ]);
        return $response;
    }

    public function score(Request $request, Response $response, $args)
    {
        $game = $this->database->queryRow("SELECT * FROM games WHERE id = ?", [$args['gameId']]);
        return GameDrawer::drawGame($this->database, $args['gameId']);
    }

    public function actions(Request $request, Response $response, $args)
    {
        $game = $this->database->queryRow("SELECT * FROM games WHERE id = ?", [$args['gameId']]);
        $data = ActionDrawer::getActionData($this->database, $args['gameId'], $_SESSION['user']);
        return $response->withJson($data);
    }

    public function cards(Request $request, Response $response, $args)
    {
        $game = $this->database->queryRow("SELECT * FROM games WHERE id = ?", [$args['gameId']]);
        return $response->withJson(CardDrawer::drawCards($this->database, $args['gameId'], $_SESSION['user']));
    }

    public function input(Request $request, Response $response, $args)
    {
        $playerId = $_SESSION['user'];
        if ($request->getParam('valueType') == 'complete') {
            $this->database->q(
                "UPDATE games_hands SET complete = 1 WHERE game_id = ? AND hand = ?",
                    [
                        $request->getParam('gameId'),
                        $request->getParam('hand')
                    ]
            );
        } elseif ($request->getParam('valueType') == 'trumps') {
            $hand = ActionDrawer::startHandIfNeeded($this->database, $request->getParam('gameId'), $_SESSION['user']);
            $this->database->q(
                "UPDATE games_hands SET trumps = ? WHERE game_id = ? AND hand = ?",
                    [
                        $request->getParam('trumps'),
                        $request->getParam('gameId'),
                        $hand
                    ]
            );
            DataRequest::addEvent( $request->getParam('gameId'), $playerId, 'Chose Trumps #SUIT-' . $request->getParam('trumps') . '#');
        } else {
            $hand = ActionDrawer::startHandIfNeeded($this->database, $request->getParam('gameId'), $_SESSION['user']);
            $this->database->q(
                "INSERT INTO games_hands_players (game_id, hand, player_id, value_type, value) VALUES (?,?,?,?,?)",
                [
                    $request->getParam('gameId'),
                    $hand,
                    $playerId,
                    $request->getParam('valueType'),
                    $request->getParam('value')
                ]
            );
        }
    }
}