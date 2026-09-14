<?php

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        return $this->json($response, User::all()->makeHidden('password'));
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $user = User::find($args['id']);

        if (!$user) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        return $this->json($response, $user->makeHidden('password'));
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        if ((int) $args['id'] !== $request->getAttribute('user_id')) {
            return $this->json($response, ['error' => 'forbidden'], 403);
        }

        $user = User::find($args['id']);

        if (!$user) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $user->fill(array_intersect_key($data, array_flip(['username', 'email'])));

        if (!empty($data['password'])) {
            $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $user->save();

        return $this->json($response, $user->makeHidden('password'));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        if ((int) $args['id'] !== $request->getAttribute('user_id')) {
            return $this->json($response, ['error' => 'forbidden'], 403);
        }

        $user = User::find($args['id']);

        if (!$user) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        $user->delete();

        return $this->json($response, null, 204);
    }
}
