<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case DAILY     = '2341022000051559';
    case WEEKLY    = '2341022000051560';
    case BI_WEEKLY = '2341022000051561';
    case MONTHLY   = '2341022000051562';

    public function durationInDays(): int
    {
        return match ($this) {
            self::DAILY     => 1,
            self::WEEKLY    => 7,
            self::BI_WEEKLY => 14,
            self::MONTHLY   => 30,
        };
    }

    /**
     * Resolve the number of days from the productId returned in the Safaricom callback.
     * Returns 1 day as default if the productId is unknown.
     */
    public static function getDaysForPlan(?string $productId): int
    {
        if (!$productId) {
            return 1;
        }

        $productId = trim($productId, '"\'  ');

        return match ($productId) {
            self::DAILY->value     => 1,
            self::WEEKLY->value    => 7,
            self::BI_WEEKLY->value => 14,
            self::MONTHLY->value   => 30,
            default                => 1,
        };
    }
}
