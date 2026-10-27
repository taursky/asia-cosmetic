<?php

namespace Database\Seeders;

use App\Models\CatalogCollection;
use Illuminate\Database\Seeder;

class CatalogCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            ['slug' => 'bestsellers', 'title' => 'Хиты продаж', 'description' => 'Популярные товары Asia Cosmetic', 'filter' => 'all', 'sort' => 'popular', 'sort_order' => 10],
            ['slug' => 'sale', 'title' => 'Акции', 'description' => 'Товары со сниженной ценой', 'filter' => 'sale', 'sort' => 'new', 'sort_order' => 20],
            ['slug' => 'new', 'title' => 'Новинки', 'description' => 'Недавно добавленные товары', 'filter' => 'new', 'sort' => 'new', 'days' => 30, 'sort_order' => 30],
            ['slug' => 'top-rated', 'title' => 'Высокий рейтинг', 'description' => 'Товары с высокими оценками', 'filter' => 'rated', 'sort' => 'rating', 'sort_order' => 40],
        ];

        foreach ($presets as $preset) {
            CatalogCollection::query()->firstOrCreate(
                ['slug' => $preset['slug']],
                $preset + ['is_active' => true, 'show_in_menu' => true, 'show_on_home' => true],
            );
        }
    }
}
