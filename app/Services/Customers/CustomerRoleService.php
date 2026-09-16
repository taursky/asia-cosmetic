<?php

namespace App\Services\Customers;

use App\Models\CustomerRole;
use App\Models\CustomerRoleHistory;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerRoleService
{
    public function evaluateAfterPaidOrder(User $user, Order $order): void
    {
        if ($user->customer_role_locked) {
            return;
        }

        $role = $this->bestQualifiedRole($user);

        if ($role) {
            $this->assign($user, $role, 'paid_order', $this->rollingPaidAmount($user, $role->qualification_period_days), null, "После оплаты заказа #{$order->id}");
        }
    }

    public function reevaluate(User $user): void
    {
        if ($user->customer_role_locked) {
            return;
        }

        $role = $this->bestQualifiedRole($user)
            ?? CustomerRole::query()->where('is_default', true)->orderBy('level')->first();

        if ($role) {
            $this->assign($user, $role, 'rolling_period', $this->rollingPaidAmount($user, $role->qualification_period_days));
        }
    }

    public function assign(User $user, CustomerRole $role, string $source, ?float $qualifiedAmount = null, ?int $changedByAdminUserId = null, ?string $comment = null): void
    {
        DB::transaction(function () use ($user, $role, $source, $qualifiedAmount, $changedByAdminUserId, $comment): void {
            $validUntil = $role->validity_days ? now()->addDays($role->validity_days) : null;
            $changed = (int) $user->customer_role_id !== (int) $role->id;

            $user->forceFill([
                'customer_role_id' => $role->id,
                'customer_role_valid_until' => $validUntil,
            ])->save();

            CustomerRoleHistory::query()->create([
                'user_id' => $user->id,
                'customer_role_id' => $role->id,
                'source' => $source,
                'qualified_amount' => $qualifiedAmount,
                'qualification_period_days' => $role->qualification_period_days,
                'valid_from' => now(),
                'valid_until' => $validUntil,
                'changed_by_admin_user_id' => $changedByAdminUserId,
                'comment' => $comment ?? ($changed ? 'Роль изменена' : 'Срок роли продлён'),
            ]);
        });
    }

    public function bestQualifiedRole(User $user): ?CustomerRole
    {
        foreach (CustomerRole::query()->where('is_auto', true)->orderByDesc('level')->get() as $role) {
            if ($this->rollingPaidAmount($user, $role->qualification_period_days) >= (float) $role->qualification_amount) {
                return $role;
            }
        }

        return null;
    }

    public function rollingPaidAmount(User $user, int $days): float
    {
        return (float) Order::query()
            ->where('user_id', $user->id)
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDays($days))
            ->sum('total');
    }
}
