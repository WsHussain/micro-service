<?php

use App\Controllers\AuthController;
use App\Controllers\MessageController;
use App\Controllers\UserController;
use App\Middleware\JwtAuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->post('/register', [AuthController::class, 'register']);
    $app->post('/login', [AuthController::class, 'login']);

    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/users', [UserController::class, 'index']);
        $group->get('/users/{id}', [UserController::class, 'show']);
        $group->put('/users/{id}', [UserController::class, 'update']);
        $group->delete('/users/{id}', [UserController::class, 'destroy']);

        $group->get('/messages', [MessageController::class, 'index']);
        $group->post('/messages', [MessageController::class, 'store']);
        $group->get('/messages/{id}', [MessageController::class, 'show']);
        $group->put('/messages/{id}', [MessageController::class, 'update']);
        $group->delete('/messages/{id}', [MessageController::class, 'destroy']);
    })->add(new JwtAuthMiddleware());
};
