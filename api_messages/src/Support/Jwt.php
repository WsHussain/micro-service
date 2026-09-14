<?php

namespace App\Support;

use Firebase\JWT\JWT as FirebaseJwt;
use Firebase\JWT\Key;

class Jwt
{
    public static function encode(int $userId): string
    {
        $now = time();
        $payload = [
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + (int) ($_ENV['JWT_TTL'] ?? 3600),
        ];

        return FirebaseJwt::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    public static function decode(string $token): array
    {
        $decoded = FirebaseJwt::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

        return (array) $decoded;
    }
}
