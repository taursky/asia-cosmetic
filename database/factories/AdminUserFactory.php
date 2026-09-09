<?php
// database/factories/AdminUserFactory.php

namespace Database\Factories;

use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Назначить случайные роли пользователю
     */
    public function withRandomRoles(int $count = 1)
    {
        return $this->afterCreating(function (AdminUser $user) use ($count) {
            $roles = \App\Models\Role::inRandomOrder()->limit($count)->get();
            $user->roles()->attach($roles);
        });
    }
}
