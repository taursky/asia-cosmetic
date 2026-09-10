<?php

namespace Database\Seeders;

use App\Models\ProductPriceType;
use Illuminate\Database\Seeder;

class ProductPriceTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'purchase', 'name' => 'Закупочная', 'sort_order' => 10],
            ['code' => 'retail', 'name' => 'Розничная', 'sort_order' => 20],
            ['code' => 'wholesale_1', 'name' => 'Оптовая-1', 'sort_order' => 30],
            ['code' => 'wholesale_2', 'name' => 'Оптовая-2', 'sort_order' => 40],
            ['code' => 'wholesale_3', 'name' => 'Оптовая-3', 'sort_order' => 40],
        ] as $type) {
            ProductPriceType::query()->updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
