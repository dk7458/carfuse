<?php

namespace App\Controllers;

use App\Services\Auth\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends Controller
{
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->authService->login($data);
        return $this->jsonResponse($response, $result);
    }

    public function register(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->authService->register($data);
        return $this->jsonResponse($response, $result);
    }

    public function refresh(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->authService->refresh($data);
        return $this->jsonResponse($response, $result);
    }

    public function logout(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->authService->logout($data);
        return $this->jsonResponse($response, $result);
    }

    public function userDetails(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        return $this->jsonResponse($response, $user);
    }
}
