<?php

namespace App\Services\V1;

use App\Http\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\Model;

class ExceptionsHandling
{
    public function ThrowExceptionIfNotFound(Model $model):void
    {
        if (!$model)
            throw new NotFoundException(get_class($model));
    }
}
