<?php

namespace App\Controllers;

use App\Models\User;
use App\Support\Jwt;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends BaseController
{
    public function register(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return $this->json($response, ['error' => 'username, email and password are required'], 422);
        }

        if (User::where('email', $data['email'])->exists()) {
            return $this->json($response, ['error' => 'email already registered'], 409);
        }

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);

        return $this->json($response, $user->makeHidden('password'), 201);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $user = User::where('email', $data['email'] ?? '')->first();

        if (!$user || !password_verify($data['password'] ?? '', $user->password)) {
            return $this->json($response, ['error' => 'invalid credentials'], 401);
        }

        $token = Jwt::encode($user->id);

        return $this->json($response, ['token' => $token, 'user' => $user->makeHidden('password')]);
    }
}
