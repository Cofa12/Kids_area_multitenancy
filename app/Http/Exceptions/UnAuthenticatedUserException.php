<?php

namespace App\Http\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class UnAuthenticatedUserException extends RuntimeException
{

    /**
     * @psalm-suppress UnusedMethod
     */
    public function render():JsonResponse
    {
        return response()->json([
            'error'=>'Email or password is incorrect'
        ],401);
    }
}
