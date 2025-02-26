<?php

namespace App\Controllers;

use App\Services\Auth\AuthService;
use App\Helpers\LoggingHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class AuthController extends Controller
{
    protected LoggerInterface $logger; // Ensure the type matches the parent class
    private AuthService $authService;

    public function __construct(
        LoggerInterface $logger,  // Parent logger
        AuthService $authService
    ) {
        parent::__construct($logger);
        $this->authService = $authService;
        $this->logger = $logger;
    }

    public function login(Request $request, Response $response)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse($response, ["error" => "Method Not Allowed"], 405);
        }

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->jsonResponse($response, ["error" => "Email and password are required"], 400);
        }

        $result = $this->authService->login($data);
        $this->logger->info('User login attempt', ['data' => $data]);

        return $this->jsonResponse($response, $result);
    }

    public function register(Request $request, Response $response)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse($response, ["error" => "Method Not Allowed"], 405);
        }

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
            return $this->jsonResponse($response, ["error" => "Name, email, and password are required"], 400);
        }

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

    public function resetPasswordRequest(Request $request, Response $response)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse($response, ["error" => "Method Not Allowed"], 405);
        }

        if (!isset($data['email'])) {
            return $this->jsonResponse($response, ["error" => "Email is required"], 400);
        }

        // Trigger password reset flow (implementation not shown)
        // ...

        return $this->jsonResponse($response, ["message" => "Password reset request received"]);
    }
}
