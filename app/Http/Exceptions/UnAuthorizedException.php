<?php

namespace App\Http\Exceptions;

use Illuminate\Http\JsonResponse;

class UnAuthorizedException extends \RuntimeException
{
    /**
     * @psalm-suppress UnusedMethod
     */
    public function render():JsonResponse
    {
        return response()->json([
            'error'=>'UnAuthorized User'
        ],JsonResponse::HTTP_UNAUTHORIZED);
    }
}
