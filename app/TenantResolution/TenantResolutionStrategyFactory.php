<?php

namespace App\TenantResolution;

use App\TenantResolution\Contracts\TenantResolutionStrategy;
use App\TenantResolution\Strategies\MtnNaijriaTenantStrategy;
use Illuminate\Http\Request;

class TenantResolutionStrategyFactory
{
    /** @var array<int, TenantResolutionStrategy> */
    private array $strategies;

    public function __construct(?array $strategies = null)
    {
        $this->strategies = $strategies ?? [
            new MtnNaijriaTenantStrategy(),
        ];
    }

    public function for(Request $request): ?TenantResolutionStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($request)) {
                return $strategy;
            }
        }

        return null;
    }
}
