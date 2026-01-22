<?php

namespace App\Http\Contracts;

use App\Models\User;

abstract class RegistrationAbstraction
{
    public function register(array $credentials): void
    {
        $user = new User();
        foreach ($credentials as $field => $value) {
            $user->$field = $value;
        }
        $user->save();
    }
}
