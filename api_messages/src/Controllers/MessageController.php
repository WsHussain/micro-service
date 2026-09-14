<?php

namespace App\Controllers;

use App\Models\Message;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MessageController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        $messages = Message::where('user_id', $request->getAttribute('user_id'))->get();

        return $this->json($response, $messages);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $message = Message::find($args['id']);

        if (!$message) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        return $this->json($response, $message);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        if (empty($data['content'])) {
            return $this->json($response, ['error' => 'content is required'], 422);
        }

        $message = Message::create([
            'user_id' => $request->getAttribute('user_id'),
            'content' => $data['content'],
        ]);

        return $this->json($response, $message, 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $message = Message::find($args['id']);

        if (!$message) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        if ($message->user_id !== $request->getAttribute('user_id')) {
            return $this->json($response, ['error' => 'forbidden'], 403);
        }

        $data = (array) $request->getParsedBody();

        if (!empty($data['content'])) {
            $message->content = $data['content'];
            $message->save();
        }

        return $this->json($response, $message);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $message = Message::find($args['id']);

        if (!$message) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        if ($message->user_id !== $request->getAttribute('user_id')) {
            return $this->json($response, ['error' => 'forbidden'], 403);
        }

        $message->delete();

        return $this->json($response, null, 204);
    }
}
