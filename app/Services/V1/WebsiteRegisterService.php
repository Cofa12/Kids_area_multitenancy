<?php

namespace App\Services\V1;

use App\Http\Contracts\RegistrationAbstraction;
use App\Http\Exceptions\AlreadyExistedException;
use App\Http\Exceptions\BadRequestException;
use App\Http\Exceptions\CodeAlreadyUsedException;
use App\Http\Exceptions\NotFoundException;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Support\Facades\Date;

class WebsiteRegisterService extends RegistrationAbstraction
{

    public function __construct(private ExceptionsHandling $exception)
    {
    }

    public function register(array $credentials): void
    {
        $user = User::where('name', $credentials['name'])->first();

        if (!$user) {
            throw new NotFoundException('user');
        }

        if (!is_null($user->phone)) {
            throw new BadRequestException('User data cannot be modified once phone number is set.');
        }

        if (isset($credentials['phone'])) {
            $phoneExists = User::where('phone', $credentials['phone'])
                ->where('id', '!=', $user->id)
                ->exists();

            if ($phoneExists) {
                throw new AlreadyExistedException();
            }
        }

        if (isset($credentials['email'])) {
            $emailExists = User::where('email', $credentials['email'])
                ->where('id', '!=', $user->id)
                ->exists();

            if ($emailExists) {
                throw new AlreadyExistedException();
            }
        }

        unset($credentials['name'], $credentials['campaign_id']);

        if (!empty($credentials['referral_code'])) {
            $this->checkAndAddOneDayToCodeOwner($credentials['referral_code'], $user);
        }

        do {
            $referralCode = (string) random_int(100000, 999999);
        } while (User::where('referral_code', $referralCode)->exists());

        $credentials['referral_code'] = $referralCode;

        foreach ($credentials as $field => $value) {
            $user->$field = $value;
        }

        $user->expiration_date = today()->addDay();

        $user->save();
    }


    private function checkAndAddOneDayToCodeOwner(string $referralCode, User $user): void
    {
        $codeOwner = User::where('referral_code', $referralCode)->first();
        $this->exception->ThrowExceptionIfNotFound($codeOwner);

        $alreadyUsed = Referral::where('owner_id', $codeOwner->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyUsed) {
            throw new CodeAlreadyUsedException();
        }

        $baseDate = $codeOwner->created_at ?: now();
        $codeOwner->referrals()->create([
            'user_id' => $user->id,
            'referred_id' => $user->id,
            'referred_at' => Date::parse($baseDate)->addDays(1)->toDateTimeString(),
        ]);
    }
}
