<?php

namespace App\Http\Exceptions;

use Illuminate\Http\JsonResponse;

class NotFoundException extends \RuntimeException
{
    public function __construct(private string $model)
    {
        parent::__construct($model);
    }

    /**
     * @psalm-suppress UnusedMethod
     */

    public function render():JsonResponse
    {
        return response()->json(['error'=>'Not Found '.$this->model],JsonResponse::HTTP_NOT_FOUND);
    }
}
