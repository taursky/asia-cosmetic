<?php

namespace App\Services\Customers;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    public function __construct(private readonly CustomerRoleService $roles) {}

    public function markPaid(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order->forceFill([
                'payment_status' => 'paid',
                'paid_at' => $order->paid_at ?? now(),
            ])->save();

            if ($order->user) {
                $this->roles->evaluateAfterPaidOrder($order->user, $order);
            }
        });
    }
}
