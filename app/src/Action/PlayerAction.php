<?php

namespace App\Action;

use App\Utility\ActionDrawer;
use App\Utility\Database;
use App\Utility\DataRequest;
use App\Utility\GameDrawer;
use Psr\Log\LoggerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Slim\Views\Twig;

class PlayerAction
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

    public function addPlayer(Request $request, Response $response, $arguments)
    {
        $this->view->render($response, 'player.twig',
            [
                'player' => [],
            ]);
        return $response;
    }

    public function addPlayerProcess(Request $request, Response $response, $arguments)
    {
        $content = base64_encode($request->getUploadedFiles()['image']->getStream()->__toString());
        $this->database->q(
            "INSERT INTO players (name, image) VALUES (?,?)",
            [
                $request->getParam('username'),
                $content
            ]
        );
        return $response->withRedirect('../../games');
    }

    public function updatePlayer(Request $request, Response $response, $arguments)
    {
        $this->view->render($response, 'player.twig',
            [
                'player' => DataRequest::getActivePlayer(),
            ]);
        return $response;
    }

    public function playCard(Request $request, Response $response, $arguments)
    {
        $player = DataRequest::getActivePlayer();
        $gameId = $request->getParam('gameId');
        $hand = ActionDrawer::startHandIfNeeded($this->database, $gameId, $player['id']);
        $turn = DataRequest::whichTurnIsIt($gameId, $hand);
        $this->database->q(
            "INSERT INTO games_hands_turns (game_id, hand, player_id, turn, card) VALUES (?,?,?,?,?)",
            [
                $request->getParam('gameId'),
                $hand,
                $player['id'],
                $turn,
                $request->getParam('card')
            ]
        );

    }

    public function updatePlayerProcess(Request $request, Response $response, $arguments)
    {
        $player = DataRequest::getActivePlayer();
        if ($request->getUploadedFiles()['image']->file) {
            $content = base64_encode($request->getUploadedFiles()['image']->getStream()->__toString());
            $this->database->q(
                "UPDATE players SET name = ?, image = ? WHERE id = ?",
                [
                    $request->getParam('username'),
                    $content,
                    $player['id']
                ]
            );
        } else {
            $this->database->q(
                "UPDATE players SET name = ? WHERE id = ?",
                [
                    $request->getParam('username'),
                    $player['id']
                ]
            );
        }
        return $response->withRedirect('../../games');
    }
}