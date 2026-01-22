<?php

namespace App\Services\V1;

use App\Models\User;

class SubscriptionHandling
{

    public function canAccessContent(User $user):bool
    {
        return $this->isSubscribed($user) && $this->isRenewable($user);
    }
    private function isSubscribed(User $user):bool
    {
        return $user->created_at <= now() || $this->isRenewable($user);
    }

    private function isRenewable(User $user):bool
    {
        return $user->referrals()->latest('referred_at')->first()?->referred_at <= now();
    }
}
