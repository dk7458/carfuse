<?php

/**
 * @deprecated This file is deprecated and will be removed in a future version.
 * Use App\Services\Auth\TokenService instead for token validation.
 */

namespace App;

class TokenValidator 
{
    /**
     * @deprecated Use App\Services\Auth\TokenService::validateTokenFromHeader() instead
     */
    public static function validateToken($tokenHeader)
    {
        trigger_error(
            'TokenValidator is deprecated. Use App\Services\Auth\TokenService::validateTokenFromHeader() instead.', 
            E_USER_DEPRECATED
        );
        
        // Get the TokenService from the service container
        $tokenService = \App\Container::getInstance()->get(\App\Services\Auth\TokenService::class);
        return $tokenService->validateTokenFromHeader($tokenHeader);
    }
}
