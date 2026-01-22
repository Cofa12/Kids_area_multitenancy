<?php

namespace App\Http\Exceptions;

use Illuminate\Http\JsonResponse;

class AlreadyExistedException extends \RuntimeException
{
    /**
     * @psalm-suppress UnusedMethod
     */
    public function render():JsonResponse
    {
        return response()->json(['error'=>'Already Existed'],JsonResponse::HTTP_CONFLICT);
    }
}
