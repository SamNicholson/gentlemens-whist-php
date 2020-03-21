<?php
// Routes

$app->get('/', App\Action\HomeAction::class . ':login')
    ->setName('login');

$app->get('/login', App\Action\HomeAction::class . ':loginProcess')
    ->setName('login');

$app->get('/games', App\Action\HomeAction::class . ':games')
    ->setName('games');

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
