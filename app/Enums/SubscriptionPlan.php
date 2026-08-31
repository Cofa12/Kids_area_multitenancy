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

    /**
     * Normalize incoming plan identifier to a standard key ('daily', 'weekly', 'bi_weekly', 'monthly').
     */
    public static function normalizePlanKey(?string $plan): string
    {
        if (!$plan) {
            return 'daily';
        }

        $clean = strtolower(trim($plan, '"\'  '));

        return match ($clean) {
            self::DAILY->value, '23410220000051559', 'daily', 'day', '1day', '1_day' => 'daily',
            self::WEEKLY->value, '23410220000051560', 'weekly', 'week', '1week', '1_week', '7days', '7_days' => 'weekly',
            self::BI_WEEKLY->value, '23410220000051561', 'bi-weekly', 'bi_weekly', 'biweekly', '2weeks', '2_weeks', '14days', '14_days' => 'bi_weekly',
            self::MONTHLY->value, '23410220000051562', 'monthly', 'month', '1month', '1_month', '30days', '30_days' => 'monthly',
            default => 'daily',
        };
    }

    /**
     * Get the price of a plan according to the tenant (e.g. Nigeria or Kenya).
     *
     * Nigeria (NGN):
     * - daily: 100
     * - weekly: 200
     * - bi_weekly: 300
     * - monthly: 500
     *
     * Kenya (KES):
     * - daily: 100
     * - weekly: 100
     * - bi_weekly: 100
     * - monthly: 100
     */
    public static function getPriceForTenant(?string $plan, ?string $tenant = null): float
    {
        $planKey = self::normalizePlanKey($plan);
        $tenantLower = strtolower($tenant ?? '');

        if (str_contains($tenantLower, 'kenya') || str_contains($tenantLower, 'safaricom')) {
            return match ($planKey) {
                'daily', 'weekly', 'bi_weekly', 'monthly' => 100.0,
                default => 100.0,
            };
        }

        // Default to Nigeria / MTN pricing
        return match ($planKey) {
            'daily'     => 100.0,
            'weekly'    => 200.0,
            'bi_weekly' => 300.0,
            'monthly'   => 500.0,
            default     => 100.0,
        };
    }
}
