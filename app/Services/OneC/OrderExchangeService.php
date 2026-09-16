<?php

namespace App\Services\OneC;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderExchangeService
{
    public function pending(int $perPage = 100): LengthAwarePaginator
    {
        return Order::query()
            ->with('items')
            ->whereIn('sync_status', ['pending', 'failed'])
            ->orderBy('id')
            ->paginate(min(max($perPage, 1), 500));
    }

    public function markStatus(Order $order, array $data): Order
    {
        $order->update([
            'one_c_id' => $data['one_c_id'] ?? $order->one_c_id,
            'one_c_number' => $data['one_c_number'] ?? $order->one_c_number,
            'status' => $data['status'],
            'payment_status' => $data['payment_status'] ?? $order->payment_status,
            'sync_status' => 'synced',
            'sync_error' => null,
            'synced_at' => now(),
        ]);

        return $order->fresh('items');
    }
}
