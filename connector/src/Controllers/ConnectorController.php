<?php

namespace App\Controllers;

use App\Services\DiscussionsApiClient;
use App\Services\MessagesApiClient;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConnectorController
{
    private MessagesApiClient $messages;
    private DiscussionsApiClient $discussions;

    public function __construct()
    {
        $this->messages = new MessagesApiClient();
        $this->discussions = new DiscussionsApiClient();
    }

    public function listDiscussions(Request $request, Response $response): Response
    {
        return $this->json($response, $this->discussions->list());
    }

    public function createDiscussion(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        if (empty($data['title']) || empty($data['participants'])) {
            return $this->json($response, ['error' => 'title and participants are required'], 422);
        }

        $discussion = $this->discussions->create($data['title'], $data['participants']);

        return $this->json($response, $discussion, 201);
    }

    public function getDiscussion(Request $request, Response $response, array $args): Response
    {
        $token = $this->bearerToken($request);

        try {
            $discussion = $this->discussions->get($args['id']);
        } catch (RequestException $e) {
            return $this->json($response, ['error' => 'discussion not found'], 404);
        }

        $discussion['messages'] = array_map(
            fn ($messageId) => $this->safeGetMessage($messageId, $token),
            $discussion['messageIds'] ?? []
        );

        return $this->json($response, $discussion);
    }

    public function addMessageToDiscussion(Request $request, Response $response, array $args): Response
    {
        $token = $this->bearerToken($request);

        if (!$token) {
            return $this->json($response, ['error' => 'missing bearer token'], 401);
        }

        $data = (array) $request->getParsedBody();

        if (empty($data['content'])) {
            return $this->json($response, ['error' => 'content is required'], 422);
        }

        $message = $this->messages->createMessage($data['content'], $token);
        $discussion = $this->discussions->addMessage($args['id'], (int) $message['id']);

        return $this->json($response, $discussion, 201);
    }

    public function deleteDiscussion(Request $request, Response $response, array $args): Response
    {
        $this->discussions->delete($args['id']);

        return $this->json($response, null, 204);
    }

    private function safeGetMessage($messageId, ?string $token): ?array
    {
        if (!$token) {
            return ['id' => $messageId];
        }

        try {
            return $this->messages->getMessage((int) $messageId, $token);
        } catch (RequestException $e) {
            return ['id' => $messageId, 'error' => 'unavailable'];
        }
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');

        return preg_match('/Bearer\s+(.*)$/i', $header, $m) ? $m[1] : null;
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write($data === null ? '' : json_encode($data));

        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
