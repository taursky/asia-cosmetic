<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Customers\CustomerRoleService;
use Illuminate\Console\Command;

class ReevaluateCustomerRoles extends Command
{
    protected $signature = 'customers:reevaluate-roles';
    protected $description = 'Пересчитать коммерческие роли покупателей';

    public function handle(CustomerRoleService $roles): int
    {
        User::query()
            ->where('is_active', true)
            ->where('customer_role_locked', false)
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($roles): void {
                foreach ($users as $user) {
                    $roles->reevaluate($user);
                }
            });

        $this->info('Роли покупателей пересчитаны.');
        return self::SUCCESS;
    }
}
