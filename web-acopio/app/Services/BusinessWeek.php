<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class BusinessWeek
{
    public static function bounds($date = null): array
    {
        $day = CarbonImmutable::parse($date ?? now())->startOfDay();
        $start = $day->subDays(($day->dayOfWeek - 4 + 7) % 7);

        return [$start, $start->addDays(6)->endOfDay()];
    }
}
