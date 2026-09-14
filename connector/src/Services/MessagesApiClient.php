<?php

namespace App\Services;

use GuzzleHttp\Client;

class MessagesApiClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['base_uri' => rtrim($_ENV['MESSAGES_API_URL'], '/') . '/']);
    }

    public function getMessage(int $id, string $token): array
    {
        $res = $this->http->get("messages/{$id}", [
            'headers' => ['Authorization' => "Bearer {$token}"],
        ]);

        return json_decode((string) $res->getBody(), true);
    }

    public function createMessage(string $content, string $token): array
    {
        $res = $this->http->post('messages', [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'json' => ['content' => $content],
        ]);

        return json_decode((string) $res->getBody(), true);
    }
}
