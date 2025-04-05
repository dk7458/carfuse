<?php
/**
 * Middleware Dependency Injection Documentation
 * 
 * This file documents the constructor dependencies for all middleware classes.
 * It's intended as reference documentation, not for execution.
 */

namespace App\Documentation;

/**
 * TokenValidationMiddleware
 * Validates JWT tokens in requests
 * 
 * @param AuthService $authService - Service for authentication operations
 * @param LoggerInterface $logger - For logging operations
 * @param TokenService $tokenService - For token validation operations
 */
function TokenValidationMiddleware_constructor(
    \App\Services\Auth\AuthService $authService,
    \Psr\Log\LoggerInterface $logger,
    \App\Services\Auth\TokenService $tokenService
) {}

/**
 * SessionMiddleware
 * Manages session state and configuration
 * 
 * @param LoggerInterface $logger - For logging operations
 * @param array $config - Configuration options for session (optional)
 */
function SessionMiddleware_constructor(
    \Psr\Log\LoggerInterface $logger,
    array $config = []
) {}

/**
 * RequireAuthMiddleware
 * Ensures user is authenticated for protected routes
 * 
 * @param LoggerInterface $logger - For logging operations
 */
function RequireAuthMiddleware_constructor(
    \Psr\Log\LoggerInterface $logger
) {}

/**
 * EncryptionMiddleware
 * Handles encryption/decryption of sensitive request/response data
 * 
 * @param LoggerInterface $logger - For logging operations
 * @param EncryptionService $encryptionService - For encryption/decryption operations
 */
function EncryptionMiddleware_constructor(
    \Psr\Log\LoggerInterface $logger,
    \App\Services\EncryptionService $encryptionService
) {}

/**
 * AuthMiddleware
 * Handles authentication from tokens and attaches user data to requests
 * 
 * @param TokenService $tokenService - For token validation operations
 * @param LoggerInterface $logger - For logging operations
 * @param DatabaseHelper $dbHelper - For database operations
 * @param bool $required - Whether authentication is required (optional)
 */
function AuthMiddleware_constructor(
    \App\Services\Auth\TokenService $tokenService,
    \Psr\Log\LoggerInterface $logger,
    \App\Helpers\DatabaseHelper $dbHelper,
    bool $required = false
) {}
