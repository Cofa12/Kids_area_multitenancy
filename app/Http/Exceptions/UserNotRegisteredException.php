<?php

namespace App\Http\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class UserNotRegisteredException extends RuntimeException
{
    /**
     * @psalm-suppress UnusedMethod
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'this user is not registered'
        ], 401);
    }
}
