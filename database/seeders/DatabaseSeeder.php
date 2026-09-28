<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // order zaroori hai: roles pehle, aur farmers se pehle markets aur categories
        $this->call([
            RolesAndPermissionsSeeder::class,
            ProductCategorySeeder::class,
            TexasMarketsSeeder::class,
            DemoAccountSeeder::class,
            DemoOrderSeeder::class,
            CommunityPostSeeder::class,
            ProactiveInboxSeeder::class,
        ]);
    }
}
