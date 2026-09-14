<?php

namespace App\Services;

use GuzzleHttp\Client;

class DiscussionsApiClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['base_uri' => rtrim($_ENV['DISCUSSIONS_API_URL'], '/') . '/']);
    }

    public function list(): array
    {
        $res = $this->http->get('discussions');

        return json_decode((string) $res->getBody(), true);
    }

    public function get(string $id): array
    {
        $res = $this->http->get("discussions/{$id}");

        return json_decode((string) $res->getBody(), true);
    }

    public function create(string $title, array $participants): array
    {
        $res = $this->http->post('discussions', [
            'json' => ['title' => $title, 'participants' => $participants],
        ]);

        return json_decode((string) $res->getBody(), true);
    }

    public function addMessage(string $discussionId, int $messageId): array
    {
        $res = $this->http->post("discussions/{$discussionId}/messages", [
            'json' => ['messageId' => $messageId],
        ]);

        return json_decode((string) $res->getBody(), true);
    }

    public function delete(string $id): void
    {
        $this->http->delete("discussions/{$id}");
    }
}
