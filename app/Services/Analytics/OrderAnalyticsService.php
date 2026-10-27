<?php

namespace App\Services\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderAnalyticsService
{
    public function get(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $key = 'orders-dashboard:v2:' . md5($from->toIso8601String() . '|' . $to->toIso8601String());

        return Cache::remember($key, now()->addSeconds(60), fn (): array => $this->calculate($from, $to));
    }

    private function calculate(CarbonImmutable $from, CarbonImmutable $to): array
    {
        // Exclusive upper bound avoids timestamp precision and end-of-day bugs.
        $start = $from->startOfDay()->toDateTimeString();
        $end = $to->addDay()->startOfDay()->toDateTimeString();
        $range = static fn ($query) => $query->where('created_at', '>=', $start)->where('created_at', '<', $end);

        $summary = $range(DB::table('orders'))
            ->selectRaw('COUNT(*) AS orders_count')
            ->selectRaw('COALESCE(SUM(total), 0) AS order_total')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END), 0) AS paid_order_total")
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_orders")
            ->selectRaw('COALESCE(SUM(discount_amount), 0) AS discounts')
            ->selectRaw('COALESCE(SUM(delivery_amount), 0) AS delivery_charged')
            ->selectRaw('COALESCE(SUM(subtotal), 0) AS subtotal')
            ->selectRaw('COUNT(DISTINCT user_id) AS unique_registered_customers')
            ->first();

        $statuses = $range(DB::table('orders'))
            ->select('status')->selectRaw('COUNT(*) AS count')
            ->groupBy('status')->orderByDesc('count')->get()->map(fn ($r) => ['status' => $r->status, 'count' => (int) $r->count])->all();

        $payments = $range(DB::table('orders'))
            ->select('payment_status')->selectRaw('COUNT(*) AS count')
            ->selectRaw('COALESCE(SUM(total), 0) AS amount')
            ->groupBy('payment_status')->orderByDesc('count')->get()
            ->map(fn ($r) => ['status' => $r->payment_status, 'count' => (int) $r->count, 'amount' => (float) $r->amount])->all();

        $daily = $range(DB::table('orders'))
            ->selectRaw('DATE(created_at) AS day')
            ->selectRaw('COUNT(*) AS orders_count')
            ->selectRaw('COALESCE(SUM(total), 0) AS amount')
            ->groupByRaw('DATE(created_at)')->orderBy('day')->get()
            ->map(fn ($r) => ['day' => $r->day, 'count' => (int) $r->orders_count, 'amount' => (float) $r->amount])->all();

        $top = DB::table('order_items AS i')
            ->join('orders AS o', 'o.id', '=', 'i.order_id')
            ->where('o.created_at', '>=', $start)->where('o.created_at', '<', $end)
            ->select('i.product_id', 'i.name', 'i.sku')
            ->selectRaw('SUM(i.quantity) AS quantity')
            ->selectRaw('SUM(i.total) AS amount')
            ->groupBy('i.product_id', 'i.name', 'i.sku')
            ->orderByDesc('quantity')->limit(8)->get()
            ->map(fn ($r) => ['name' => $r->name, 'sku' => $r->sku, 'quantity' => (float) $r->quantity, 'amount' => (float) $r->amount])->all();

        $newCustomers = DB::query()->fromSub(
            DB::table('orders')->whereNotNull('user_id')->select('user_id')
                ->selectRaw('MIN(created_at) AS first_order_at')->groupBy('user_id'),
            'first_orders'
        )->where('first_order_at', '>=', $start)->where('first_order_at', '<', $end)->count();

        $count = (int) $summary->orders_count;
        return [
            'orders_count' => $count,
            'order_total' => (float) $summary->order_total,
            'paid_order_total' => (float) $summary->paid_order_total,
            'paid_orders' => (int) $summary->paid_orders,
            'average_check' => $count ? (float) $summary->order_total / $count : 0,
            'discounts' => (float) $summary->discounts,
            'delivery_charged' => (float) $summary->delivery_charged,
            'unique_customers' => (int) $summary->unique_registered_customers,
            'new_customers' => (int) $newCustomers,
            'statuses' => $statuses,
            'payments' => $payments,
            'daily' => $daily,
            'top_products' => $top,
        ];
    }
}
