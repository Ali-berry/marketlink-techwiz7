<?php

namespace App\Helpers;

// haversine formula se seedhi doori, miles mein (Texas hai).
// markets-distance-sort.js browser mein yahi hisaab karta hai
class DistanceHelper
{
    private const EARTH_RADIUS_MILES = 3958.8;

    public static function distanceInMiles(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return self::EARTH_RADIUS_MILES * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
