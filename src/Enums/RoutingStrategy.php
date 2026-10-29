<?php

declare(strict_types=1);

namespace Aeitron\Enums;

final class RoutingStrategy
{
    public const LATENCY_OPTIMAL = 'LATENCY_OPTIMAL';
    public const COST_MINIMAL = 'COST_MINIMAL';
    public const CARBON_NEUTRAL = 'CARBON_NEUTRAL';
    public const SOVEREIGN_PINNED = 'SOVEREIGN_PINNED';

    private function __construct() {}
}
