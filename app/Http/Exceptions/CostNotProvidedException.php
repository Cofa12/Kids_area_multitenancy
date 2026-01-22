<?php

namespace App\Http\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class CostNotProvidedException extends RuntimeException
{
    public function __construct(private string $lang)
    {
        parent::__construct($lang);
    }

    /**
     * @psalm-suppress UnusedMethod
     */

    public function render():JsonResponse
    {
        return response()->json([
            'message'=>$this->lang == 'en'? 'must has cost with influencer':'يجب إدخال cost'
        ],JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
}
