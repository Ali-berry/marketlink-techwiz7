<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Vegetables' => 'tabler:carrot',
            'Fruits' => 'tabler:apple',
            'Dairy & eggs' => 'tabler:egg',
            'Baked goods' => 'tabler:bread',
            'Honey & preserves' => 'tabler:bottle',
            'Herbs' => 'tabler:leaf',
        ];

        $position = 1;

        foreach ($categories as $categoryName => $iconName) {
            ProductCategory::updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'icon' => $iconName, 'sort_order' => $position++],
            );
        }
    }
}
