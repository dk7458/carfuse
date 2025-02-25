<?php

namespace App\Controllers;

use App\Services\Auth\AuthService;
use App\Helpers\LoggingHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends Controller
{
    private $authService;
    private $logger;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->logger = LoggingHelper::getLoggerByCategory('auth');
    }

    public function login(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->authService->login($data);
        $this->logger->info('User login attempt', ['data' => $data]);
        return $this->jsonResponse($response, $result);
    }

    public function register(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->authService->register($data);
        $this->logger->info('User registration attempt', ['data' => $data]);
        return $this->jsonResponse($response, $result);
    }

    public function refresh(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->authService->refresh($data);
        $this->logger->info('Token refresh attempt', ['data' => $data]);
        return $this->jsonResponse($response, $result);
    }

    public function logout(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->authService->logout($data);
        $this->logger->info('User logout attempt', ['data' => $data]);
        return $this->jsonResponse($response, $result);
    }

    public function userDetails(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $this->logger->info('User details retrieved', ['user' => $user]);
        return $this->jsonResponse($response, $user);
    }
}
