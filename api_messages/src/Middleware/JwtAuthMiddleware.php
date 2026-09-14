<?php

namespace App\Middleware;

use App\Support\Jwt;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');

        if (!preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $this->unauthorized('Missing bearer token');
        }

        try {
            $payload = Jwt::decode($matches[1]);
        } catch (\Throwable $e) {
            return $this->unauthorized('Invalid or expired token');
        }

        $request = $request->withAttribute('user_id', (int) $payload['sub']);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $response = new Response(401);
        $response->getBody()->write(json_encode(['error' => $message]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
