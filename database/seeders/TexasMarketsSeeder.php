<?php

namespace Database\Seeders;

use App\Models\Market;
use Illuminate\Database\Seeder;

// saari markets. El Paso Mountain time, baqi Central - har market ka apna timezone.
// har market ki cover photo aur detail banner ke liye "-wide" copy
class TexasMarketsSeeder extends Seeder
{
    public function run(): void
    {
        $markets = [
            [
                'name' => 'Houston Farmers Market',
                'slug' => 'houston-farmers-market',
                'description' => 'Long-running indoor/outdoor market on Airline Drive with produce, meat and international grocers.',
                'address' => '2520 Airline Dr, Houston, TX 77009',
                'city' => 'Houston',
                'latitude' => 29.78730000,
                'longitude' => -95.39100000,
                'operating_days' => ['saturday', 'sunday'],
                'opens_at' => '08:00',
                'closes_at' => '17:00',
                'timezone' => 'America/Chicago',
                'cover_image_path' => 'images/markets/market-tent-row.webp',
            ],
            [
                'name' => 'Dallas Farmers Market',
                'slug' => 'dallas-farmers-market',
                'description' => 'Downtown Dallas market with year-round produce sheds and a weekend artisan market.',
                'address' => '920 S Harwood St, Dallas, TX 75201',
                'city' => 'Dallas',
                'latitude' => 32.77570000,
                'longitude' => -96.78520000,
                'operating_days' => ['saturday', 'sunday'],
                'opens_at' => '10:00',
                'closes_at' => '17:00',
                'timezone' => 'America/Chicago',
                'cover_image_path' => 'images/markets/market-covered-shed.webp',
            ],
            [
                'name' => 'Pearl Farmers Market',
                'slug' => 'pearl-farmers-market',
                'description' => 'Riverwalk-adjacent market at the Pearl development, local farms and prepared foods.',
                'address' => '303 Pearl Pkwy, San Antonio, TX 78215',
                'city' => 'San Antonio',
                'latitude' => 29.33130000,
                'longitude' => -98.47890000,
                'operating_days' => ['saturday'],
                'opens_at' => '09:00',
                'closes_at' => '13:00',
                'timezone' => 'America/Chicago',
                'cover_image_path' => 'images/markets/market-burlap-stall.webp',
            ],
            [
                'name' => "Sustainable Food Center Farmers' Market",
                'slug' => 'sfc-farmers-market-austin',
                'description' => 'SFC\'s downtown Austin market at Republic Square, all vendor-grown or vendor-made.',
                'address' => '422 Guadalupe St, Austin, TX 78701',
                'city' => 'Austin',
                'latitude' => 30.26860000,
                'longitude' => -97.74930000,
                'operating_days' => ['saturday'],
                'opens_at' => '09:00',
                'closes_at' => '13:00',
                'timezone' => 'America/Chicago',
                'cover_image_path' => 'images/markets/market-park-tents.webp',
            ],
            [
                'name' => 'Cowtown Farmers Market',
                'slug' => 'cowtown-farmers-market',
                'description' => 'Year-round Fort Worth market known for its Saturday produce and local honey stalls.',
                'address' => '3821 Southwest Blvd, Fort Worth, TX 76116',
                'city' => 'Fort Worth',
                'latitude' => 32.71570000,
                'longitude' => -97.38970000,
                'operating_days' => ['wednesday', 'saturday'],
                'opens_at' => '08:00',
                'closes_at' => '14:00',
                'timezone' => 'America/Chicago',
                'cover_image_path' => 'images/markets/market-street-bunting.webp',
            ],
            [
                'name' => 'El Paso Farmers Market',
                'slug' => 'el-paso-farmers-market',
                'description' => 'Ascarate Park market with regional produce, pecans and border-region specialties.',
                'address' => 'Ascarate Park, El Paso, TX 79905',
                'city' => 'El Paso',
                'latitude' => 31.73070000,
                'longitude' => -106.40670000,
                'operating_days' => ['saturday'],
                'opens_at' => '08:00',
                'closes_at' => '12:00',
                'timezone' => 'America/Denver',
                'cover_image_path' => 'images/markets/market-desert-stall.webp',
            ],
        ];

        foreach ($markets as $marketDetails) {
            Market::updateOrCreate(['slug' => $marketDetails['slug']], $marketDetails);
        }
    }
}
