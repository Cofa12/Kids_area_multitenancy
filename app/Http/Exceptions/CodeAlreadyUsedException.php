<?php

namespace App\Http\Exceptions;

use RuntimeException;

class CodeAlreadyUsedException extends RuntimeException
{
    public function render()
    {
        $land = app()->getLocale();
        return response()->json(['message' => $land=='ar'?"اتم استخدام هذا الكود بالفعل":'Code already used'], 400);
    }
}
