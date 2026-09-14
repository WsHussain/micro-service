<?php

use App\Controllers\ConnectorController;
use Slim\App;

return function (App $app) {
    $app->get('/discussions', [ConnectorController::class, 'listDiscussions']);
    $app->post('/discussions', [ConnectorController::class, 'createDiscussion']);
    $app->get('/discussions/{id}', [ConnectorController::class, 'getDiscussion']);
    $app->post('/discussions/{id}/messages', [ConnectorController::class, 'addMessageToDiscussion']);
    $app->delete('/discussions/{id}', [ConnectorController::class, 'deleteDiscussion']);
};
