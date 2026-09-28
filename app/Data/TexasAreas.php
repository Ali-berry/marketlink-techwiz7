<?php

namespace App\Data;

use App\Helpers\DistanceHelper;

// Google Places key aane tak registration ke "Area/Location" ka fallback.
// coordinates asli shehron ke center ke qareeb hain
class TexasAreas
{
    public static function all(): array
    {
        return [
            ['name' => 'Houston', 'latitude' => 29.7604, 'longitude' => -95.3698],
            ['name' => 'San Antonio', 'latitude' => 29.4241, 'longitude' => -98.4936],
            ['name' => 'Dallas', 'latitude' => 32.7767, 'longitude' => -96.7970],
            ['name' => 'Austin', 'latitude' => 30.2672, 'longitude' => -97.7431],
            ['name' => 'Fort Worth', 'latitude' => 32.7555, 'longitude' => -97.3308],
            ['name' => 'El Paso', 'latitude' => 31.7619, 'longitude' => -106.4850],
            ['name' => 'Arlington', 'latitude' => 32.7357, 'longitude' => -97.1081],
            ['name' => 'Corpus Christi', 'latitude' => 27.8006, 'longitude' => -97.3964],
            ['name' => 'Plano', 'latitude' => 33.0198, 'longitude' => -96.6989],
            ['name' => 'Laredo', 'latitude' => 27.5306, 'longitude' => -99.4803],
            ['name' => 'Lubbock', 'latitude' => 33.5779, 'longitude' => -101.8552],
            ['name' => 'Garland', 'latitude' => 32.9126, 'longitude' => -96.6389],
            ['name' => 'Irving', 'latitude' => 32.8140, 'longitude' => -96.9489],
            ['name' => 'Amarillo', 'latitude' => 35.2220, 'longitude' => -101.8313],
            ['name' => 'Grand Prairie', 'latitude' => 32.7459, 'longitude' => -96.9978],
            ['name' => 'Brownsville', 'latitude' => 25.9017, 'longitude' => -97.4975],
            ['name' => 'McKinney', 'latitude' => 33.1972, 'longitude' => -96.6398],
            ['name' => 'Frisco', 'latitude' => 33.1507, 'longitude' => -96.8236],
            ['name' => 'Pasadena', 'latitude' => 29.6911, 'longitude' => -95.2091],
            ['name' => 'Mesquite', 'latitude' => 32.7668, 'longitude' => -96.5992],
            ['name' => 'McAllen', 'latitude' => 26.2034, 'longitude' => -98.2300],
            ['name' => 'Killeen', 'latitude' => 31.1171, 'longitude' => -97.7278],
            ['name' => 'Waco', 'latitude' => 31.5493, 'longitude' => -97.1467],
            ['name' => 'Carrollton', 'latitude' => 32.9756, 'longitude' => -96.8899],
            ['name' => 'Denton', 'latitude' => 33.2148, 'longitude' => -97.1331],
            ['name' => 'Midland', 'latitude' => 31.9973, 'longitude' => -102.0779],
            ['name' => 'Abilene', 'latitude' => 32.4487, 'longitude' => -99.7331],
            ['name' => 'Beaumont', 'latitude' => 30.0802, 'longitude' => -94.1266],
            ['name' => 'Round Rock', 'latitude' => 30.5083, 'longitude' => -97.6789],
            ['name' => 'College Station', 'latitude' => 30.6280, 'longitude' => -96.3344],
        ];
    }

    // user pe sirf coordinates save hote hain, area ka naam nahi - is liye qareeb wala area dhoond ke "Dallas" dikhate hain
    public static function nearestTo(float $latitude, float $longitude): array
    {
        return collect(self::all())
            ->sortBy(fn (array $area) => DistanceHelper::distanceInMiles($latitude, $longitude, $area['latitude'], $area['longitude']))
            ->first();
    }
}
