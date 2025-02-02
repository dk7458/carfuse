<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\EncryptionService;

class EncryptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Encrypt sensitive data in the request
        if ($this->isSensitiveEndpoint($request)) {
            $this->encryptRequestData($request);
        }

        $response = $next($request);

        // Encrypt sensitive data in the response
        if ($this->isSensitiveEndpoint($request)) {
            $this->encryptResponseData($response);
        }

        return $response;
    }

    /**
     * Determine if the request is for a sensitive endpoint.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    private function isSensitiveEndpoint(Request $request): bool
    {
        $sensitiveEndpoints = [
            '/user/profile-data',
            // Add other sensitive endpoints here
        ];

        return in_array($request->path(), $sensitiveEndpoints);
    }

    /**
     * Encrypt sensitive data in the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function encryptRequestData(Request $request): void
    {
        $data = $request->all();
        foreach ($data as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $data[$key] = EncryptionService::encrypt($value);
            }
        }
        $request->merge($data);
    }

    /**
     * Encrypt sensitive data in the response.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    private function encryptResponseData($response): void
    {
        $data = $response->getContent();
        $encryptedData = EncryptionService::encrypt($data);
        $response->setContent($encryptedData);
    }

    /**
     * Determine if the field is sensitive.
     *
     * @param  string  $field
     * @return bool
     */
    private function isSensitiveField(string $field): bool
    {
        $sensitiveFields = [
            'password',
            'email',
            'phone',
            // Add other sensitive fields here
        ];

        return in_array($field, $sensitiveFields);
    }
}
