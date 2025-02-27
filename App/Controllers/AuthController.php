<?php

namespace App\Controllers;

use App\Services\Auth\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class AuthController extends Controller
{
    protected LoggerInterface $logger;
    private AuthService $authService;

    public function __construct(
        LoggerInterface $logger,
        AuthService $authService
    ) {
        parent::__construct($logger);
        $this->authService = $authService;
        $this->logger = $logger;
    }

    public function login(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->jsonResponse($response, ["error" => "Email and password are required"], 400);
        }

        $result = $this->authService->login($data);
        $this->logger->info('User login attempt', ['data' => $data]);

        return $this->jsonResponse($response, $result);
    }

    public function register(Request $request, Response $response)
{
    // Use getParsedBody() since the parsed body was set in index.php
    $data = $request->getParsedBody();
    
    if (!is_array($data)) {
        $this->logger->error("Parsed body is not an array or is null.");
        return $this->jsonResponse($response, ["error" => "Invalid JSON input"], 400);
    }

    $this->logger->debug("Parsed request data in register: " . print_r($data, true));

    $requiredFields = ['name', 'email', 'password'];
    $missingFields = array_diff($requiredFields, array_keys($data));

    if (!empty($missingFields)) {
        $this->logger->warning("Missing required fields in register: " . implode(', ', $missingFields));
        return $this->jsonResponse($response, ["error" => "Missing fields: " . implode(', ', $missingFields)], 400);
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
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['email'])) {
            return $this->jsonResponse($response, ["error" => "Email is required"], 400);
        }

        // Trigger password reset flow (implementation not shown)
        // ...

        return $this->jsonResponse($response, ["message" => "Password reset request received"]);
    }
}
