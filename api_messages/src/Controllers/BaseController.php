<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

abstract class BaseController
{
    protected function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write($data === null ? '' : json_encode($data));

        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
