<?php
// database/seeders/AdminUserSeeder.php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Создаем роли
        $roles = [
            ['name' => 'Супер администратор', 'slug' => 'super-admin', 'description' => 'Полный доступ ко всем функциям', 'is_active' => true],
            ['name' => 'Администратор', 'slug' => 'admin', 'description' => 'Ограниченный доступ к админ-панели', 'is_active' => true],
            ['name' => 'Менеджер', 'slug' => 'manager', 'description' => 'Доступ к управлению контентом', 'is_active' => true],
            ['name' => 'Наблюдатель', 'slug' => 'viewer', 'description' => 'Только просмотр', 'is_active' => true],
            ['name' => 'Заблокированный', 'slug' => 'blocked', 'description' => 'Заблокированная роль', 'is_active' => false],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }

        // Получаем все роли
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $viewerRole = Role::where('slug', 'viewer')->first();

        // Создаем супер-администратора с несколькими ролями
        $superAdmin = AdminUser::firstOrCreate(
            ['email' => 'taursky@gmail.com'],
            [
                'name' => 'Супер Администратор',
                'phone' => '+7 (914) 791-43-93',
                'password' => Hash::make('qwertyzxc'),
            ]
        );
        $superAdmin->roles()->sync([$superAdminRole->id, $adminRole->id]);

        // Создаем администратора
//        $admin = AdminUser::firstOrCreate(
//            ['email' => 'admin@example.com'],
//            [
//                'name' => 'Администратор',
//                'phone' => '+7 (999) 222-22-22',
//                'password' => Hash::make('password123'),
//            ]
//        );
//        $admin->roles()->sync([$adminRole->id]);

        // Создаем менеджера
//        $manager = AdminUser::firstOrCreate(
//            ['email' => 'manager@example.com'],
//            [
//                'name' => 'Менеджер',
//                'phone' => '+7 (999) 333-33-33',
//                'password' => Hash::make('password123'),
//            ]
//        );
//        $manager->roles()->sync([$managerRole->id, $viewerRole->id]);

        // Создаем 10 случайных пользователей со случайными ролями
//        AdminUser::factory(10)->withRandomRoles(rand(1, 3))->create();
    }
}
