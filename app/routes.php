<?php
// Routes

$app->get('/', App\Action\HomeAction::class . ':login')
    ->setName('login');

$app->get('/login', App\Action\HomeAction::class . ':loginProcess')
    ->setName('login');

$app->get('/games', App\Action\HomeAction::class . ':games')
    ->setName('games');

$app->get('/statistics', App\Action\HomeAction::class . ':statistics')
    ->setName('statistics');

$app->get('/games/create', App\Action\HomeAction::class . ':gameAdd')
    ->setName('gameAdd');

$app->post('/games/create/process', App\Action\HomeAction::class . ':gameAddProcess')
    ->setName('gameAddProcess');

$app->get('/games/details/{gameId}', App\Action\GameAction::class . ':game')
    ->setName('game');

$app->get('/games/score/{gameId}', App\Action\GameAction::class . ':score')
    ->setName('score');

$app->get('/games/actions/{gameId}', App\Action\GameAction::class . ':actions')
    ->setName('actions');

$app->get('/games/cards/{gameId}', App\Action\GameAction::class . ':cards')
    ->setName('cards');

$app->get('/games/input', App\Action\GameAction::class . ':input')
    ->setName('input');

$app->get('/players/add', App\Action\PlayerAction::class . ':addPlayer')
    ->setName('addPlayer');

$app->post('/players/add/process', App\Action\PlayerAction::class . ':addPlayerProcess')
    ->setName('addPlayerProcess');

$app->get('/players/update', App\Action\PlayerAction::class . ':updatePlayer')
    ->setName('updatePlayer');

$app->post('/players/update/process', App\Action\PlayerAction::class . ':updatePlayerProcess')
    ->setName('addPlayerProcess');

$app->get('/players/update/play-card', App\Action\PlayerAction::class . ':playCard')
    ->setName('playCard');