<?php

namespace App\Http\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class BadRequestException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public function render():JsonResponse
    {
        return response()->json([
            'message' => $this->message,
        ],Response::HTTP_BAD_REQUEST);
    }
}
