<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case DAILY = '2341022000051559';
    case WEEKLY = '2341022000051560';
    case BI_WEEKLY = '2341022000051561';
    case MONTHLY = '2341022000051562';

    public function durationInDays(): int
    {
        return match ($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::BI_WEEKLY => 14,
            self::MONTHLY => 30,
        };
    }

    /**
     * Resolve duration in days from plan ID, pack name, or keyword.
     */
    public static function getDaysForPlan(?string $planIdentifier): int
    {
        if (!$planIdentifier) {
            return 1;
        }

        $clean = trim($planIdentifier, '"\' ');

        return match ($clean) {
            self::DAILY->value, 'Reycreo Daily', 'REYD' => 1,
            self::WEEKLY->value, 'Reycreo Weekly', 'REYW' => 7,
            self::BI_WEEKLY->value, 'Reycreo Bi-Weekly', 'REYBI' => 14,
            self::MONTHLY->value, 'Reycreo Monthly', 'REYM' => 30,
            default => 1,
        };
    }
}
