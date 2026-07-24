<?php

namespace App\Services\V1;

use App\Models\User;

class SubscriptionHandling
{
    public function canAccessContent(User $user): bool
    {
        return $this->isSubscribed($user);
    }

    private function isSubscribed(User $user): bool
    {
        if (!$user->subscription_status) {
            return false;
        }

        // expiration_date is cast to datetime in User model
        if ($user->expiration_date && $user->expiration_date->isPast()) {
            return false;
        }

        return true;
    }
}
