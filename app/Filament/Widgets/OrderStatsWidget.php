<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderStatsWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.order-stats-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public string $period = 'month';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->setDefaultDates();
    }

    public function updatedPeriod(): void
    {
        $this->setDefaultDates();
    }

    protected function setDefaultDates(): void
    {
        [$from, $to] = match ($this->period) {
            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
            ],

            '7days' => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
            ],

            'month' => [
                now()->startOfMonth(),
                now()->endOfDay(),
            ],

            default => [
                $this->dateFrom
                    ? Carbon::parse($this->dateFrom)->startOfDay()
                    : now()->startOfMonth(),

                $this->dateTo
                    ? Carbon::parse($this->dateTo)->endOfDay()
                    : now()->endOfDay(),
            ],
        };

        if ($this->period !== 'custom') {
            $this->dateFrom = $from->toDateString();
            $this->dateTo = $to->toDateString();
        }
    }

    protected function getPeriod(): array
    {
        $from = Carbon::parse(
            $this->dateFrom ?? now()->startOfMonth()
        )->startOfDay();

        $to = Carbon::parse(
            $this->dateTo ?? now()
        )->endOfDay();

        return [$from, $to];
    }

    public function getStatsProperty(): array
    {
        [$from, $to] = $this->getPeriod();

        $cacheKey = sprintf(
            'admin:orders:analytics:%s:%s',
            $from->format('Ymd'),
            $to->format('Ymd')
        );

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn() => $this->loadStats($from, $to)
        );
    }

    protected function periodQuery(Carbon $from, Carbon $to): Builder
    {
        return Order::query()
            ->whereBetween('created_at', [$from, $to]);
    }

    protected function loadStats(Carbon $from, Carbon $to): array
    {
        /*
         * Общие суммы.
         */
        $orderStats = [];
//            $this->periodQuery($from, $to)
//            ->selectRaw('COUNT(*) AS orders_count')
//            ->first();

        /*
         * Себестоимость закупленных товаров.
         *
         * purchase_price — добавленное вами поле.
         */
        $purchaseCost = [];
//            OrderItem::query()
//            ->join(
//                'orders',
//                'orders.id',
//                '=',
//                'order_items.order_id'
//            )
//            ->whereBetween(
//                'orders.created_at',
//                [$from, $to]
//            )
//            ->selectRaw(
//                'COALESCE(SUM(
//                    order_items.purchase_price
//                    * order_items.quantity
//                ), 0) AS total'
//            )
//            ->value('total');

        /*
         * Все скидки.
         *
         * ВАЖНО:
         * это корректно только если total_discounts
         * не включает promocode_discount / applied_bonus.
         */
        $discounts = 500;
//            (float)$orderStats->product_discounts
//            + (float)$orderStats->promocode_discounts
//            + (float)$orderStats->applied_bonus;

        /*
         * Расходы на доставку.
         *
         * Лучше actual_shipping, потому что total_shipping —
         * это стоимость доставки для клиента.
         */
        $shippingExpenses = 100;//(float)$orderStats->actual_shipping;

        /*
         * Если actual_shipping ещё не заполнен,
         * можно использовать плановую стоимость.
         */
//        if ($shippingExpenses <= 0) {
//            $shippingExpenses =
//                (float)$orderStats->shipping_income;
//        }

        $revenue = 1;//(float)$orderStats->revenue;

        /*
         * Прочие расходы проекта пока 0.
         *
         * Позже сюда подключаем таблицу project_expenses.
         */
        $projectExpenses = $this->getProjectExpenses(
            $from,
            $to
        );

        /*
         * total_paid уже рассчитан ПОСЛЕ скидок.
         *
         * Поэтому discounts второй раз из profit
         * НЕ вычитаем.
         */
        $profit =
            $revenue
            - (float)$purchaseCost
            - $shippingExpenses
            - $projectExpenses;

        return [
            'orders_count' => 5,//(int)$orderStats->orders_count,

            'revenue' => $revenue,

            'purchase_cost' => 10,//(float)$purchaseCost,

            'shipping_expenses' => 25,//$shippingExpenses,

            'discounts' => 0,//$discounts,

            'project_expenses' => 10,//$projectExpenses,

            'profit' => 15,//$profit,

            'new_customers' => 3,
//                $this->getNewCustomersCount(
//                $from,
//                $to
//            ),

            'speed' => 8,
//                $this->getSpeedStats(
//                $from,
//                $to
//            ),

            'statuses' => 12,
//                $this->getStatusStats(
//                $from,
//                $to
//            ),

            'problems' => 9,
//                $this->getProblemStats(),
        ];
    }

    protected function getNewCustomersCount(Carbon $from, Carbon $to): int
    {
        $firstOrders = Order::query()
            ->whereNotNull('user_id')
            ->selectRaw('
            user_id,
            MIN(created_at) AS first_order_at
        ')
            ->groupBy('user_id');

        return DB::query()
            ->fromSub($firstOrders, 'first_orders')
            ->whereBetween(
                'first_order_at',
                [$from, $to]
            )
            ->count();
    }

    protected function getSpeedStats(Carbon $from, Carbon $to): array
    {
        $slaDays = (int)config(
            'order_dashboard.sla_days',
            35
        );

        $query = $this->periodQuery($from, $to)
            ->whereNotNull('delivery_date');

        $completed = (clone $query)->count();

        if ($completed === 0) {
            return [
                'average_days' => 0,
                'on_time_count' => 0,
                'on_time_percent' => 0,
                'sla_days' => $slaDays,
            ];
        }

        /*
         * MySQL / MariaDB.
         */
        $averageDays = (clone $query)
            ->selectRaw(
                'AVG(
                TIMESTAMPDIFF(
                    DAY,
                    created_at,
                    delivery_date
                )
            ) AS average_days'
            )
            ->value('average_days');

        $onTime = (clone $query)
            ->whereRaw(
                'TIMESTAMPDIFF(
                DAY,
                created_at,
                delivery_date
            ) <= ?',
                [$slaDays]
            )
            ->count();

        return [
            'average_days' => round(
                (float)$averageDays,
                1
            ),

            'on_time_count' => $onTime,

            'on_time_percent' => round(
                ($onTime / $completed) * 100,
                1
            ),

            'sla_days' => $slaDays,
        ];
    }

    protected function getStatusStats(Carbon $from, Carbon $to): array
    {
        return [
            'processing' => $this->countStatusGroup(
                $from,
                $to,
                'processing'
            ),

            'ready_to_ship' => $this->countStatusGroup(
                $from,
                $to,
                'ready_to_ship'
            ),

            'in_transit' => $this->countStatusGroup(
                $from,
                $to,
                'in_transit'
            ),

            'ready_for_pickup' => $this->countStatusGroup(
                $from,
                $to,
                'ready_for_pickup'
            ),
        ];
    }

    protected function countStatusGroup(Carbon $from, Carbon $to, string $group): int
    {
        $statuses = config(
            "order_dashboard.statuses.{$group}",
            []
        );

        if (empty($statuses)) {
            return 0;
        }

        return $this->periodQuery($from, $to)
            ->whereHas(
                'status',
                fn(Builder $query) => $query->whereIn('name', $statuses)
            )
            ->count();
    }

    protected function getProblemStats(): array
    {
        $completed = config(
            'order_dashboard.statuses.completed',
            []
        );

        $cancelled = config(
            'order_dashboard.statuses.cancelled',
            []
        );

        $finished = array_merge(
            $completed,
            $cancelled
        );

        $slaDays = (int) config(
            'order_dashboard.sla_days',
            35
        );

        $abandonedDays = (int) config(
            'order_dashboard.problems.abandoned_after_days',
            5
        );

        /*
         * Просроченные:
         * заказ существует дольше SLA,
         * но ещё не завершён.
         */
        $overdue = Order::query()
            ->where(
                'created_at',
                '<',
                now()->subDays($slaDays)
            )
            ->whereDoesntHave(
                'status',
                fn (Builder $query) =>
                $query->whereIn('name', $finished)
            )
            ->count();

        /*
         * Брошенные:
         * активный заказ давно не менялся.
         */
        $abandoned = Order::query()
            ->where(
                'updated_at',
                '<',
                now()->subDays($abandonedDays)
            )
            ->whereDoesntHave(
                'status',
                fn (Builder $query) =>
                $query->whereIn('name', $finished)
            )
            ->count();

        /*
         * Задержка доставки:
         * delivery_date уже прошла,
         * но заказ ещё не завершён.
         */
        $deliveryDelay = Order::query()
            ->whereNotNull('delivery_date')
            ->where(
                'delivery_date',
                '<',
                now()
            )
            ->whereDoesntHave(
                'status',
                fn (Builder $query) =>
                $query->whereIn('name', $finished)
            )
            ->count();

        $awaitingStatuses = config(
            'order_dashboard.statuses.awaiting_consultant',
            []
        );

        $awaitingConsultant = empty($awaitingStatuses)
            ? 0
            : Order::query()
                ->whereHas(
                    'status',
                    fn (Builder $query) =>
                    $query->whereIn(
                        'name',
                        $awaitingStatuses
                    )
                )
                ->count();

        return [
            'overdue' => $overdue,
            'abandoned' => $abandoned,
            'delivery_delay' => $deliveryDelay,
            'awaiting_consultant' => $awaitingConsultant,
        ];
    }

    protected function getProjectExpenses(Carbon $from, Carbon $to): float
    {
        //TODO придумать откуда брать источник расходов
        return 0;
    }
}
