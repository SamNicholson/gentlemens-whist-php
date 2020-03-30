<?php
namespace App\Action;

use App\Utility\Database;
use App\Utility\DataRequest;
use App\Utility\GameDrawer;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

final class HomeAction
{
    private $view;
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

    public function login(Request $request, Response $response, $args)
    {
        $players = DataRequest::getAllPlayers();
        $this->view->render($response, 'login.twig',
            [
                'players' => $players
            ]);
        return $response;
    }

    public function loginProcess(Request $request, Response $response, $args)
    {
        $_SESSION['user'] = $request->getParam('user');
        return $response->withRedirect('games');
    }

    public function games(Request $request, Response $response, $args)
    {
        $this->view->render($response, 'games.twig',
            [
                'completeGames'   => DataRequest::getCompleteGames(),
                'unCompleteGames' => DataRequest::getNonCompleteGames()
            ]);
        return $response;
    }

    public function gameAdd(Request $request, Response $response, $args)
    {
        $players = $this->database->q(
            "SELECT * FROM players"
        );
        $this->view->render($response, 'game-add.twig',
            [
                'players' => $players
            ]);
        return $response;
    }

    public function gameAddProcess(Request $request, Response $response, $args)
    {
        $this->database->q(
            "INSERT INTO games (name, start_time, completed) VALUES (?,NOW(),0)",
            [
                implode($request->getParam('nickname', ', '))
            ]
        );
        $gameId = $this->database->getInsertId();
        foreach ($request->getParam('players') as $player) {


            $this->database->q(
                "INSERT INTO games_players (game_id, player_id, nickname, `order`) VALUES (?,?,?,?)",
                [
                    $gameId,
                    $player,
                    $request->getParam('nickname')[$player],
                    $request->getParam('order')[$player],
                ]
            );
        }
        return $response->withRedirect('../details/' . $gameId);
    }
}
