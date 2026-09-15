<?php

namespace Database\Seeders;

use App\Models\CustomerRole;
use Illuminate\Database\Seeder;

class CustomerRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => 'retail', 'name' => 'Розничный покупатель', 'sort_order' => 10],
            ['code' => 'wholesale_1', 'name' => 'Оптовый покупатель 1', 'sort_order' => 20],
            ['code' => 'wholesale_2', 'name' => 'Оптовый покупатель 2', 'sort_order' => 30],
            ['code' => 'vip', 'name' => 'VIP покупатель', 'sort_order' => 40],
        ];

        foreach ($roles as $role) {
            CustomerRole::query()->updateOrCreate(
                ['code' => $role['code']],
                $role + ['is_active' => true],
            );
        }
    }
}
