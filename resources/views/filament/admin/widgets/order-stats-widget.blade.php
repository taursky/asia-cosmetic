<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Аналитика заказов</x-slot>
        <x-slot name="description">Статистика по дате создания заказа. Сумма заказов не равна фактически полученным платежам.</x-slot>

        <div class="flex flex-wrap items-end gap-3 mb-6">
            <label class="text-sm font-medium">Период
                <select wire:model.live="period" class="mt-1 block rounded-lg border-gray-300 bg-white text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="today">Сегодня</option>
                    <option value="7days">7 дней</option>
                    <option value="30days">30 дней</option>
                    <option value="month">Этот месяц</option>
                    <option value="previous_month">Прошлый месяц</option>
                    <option value="year">Этот год</option>
                    <option value="custom">Свой период</option>
                </select>
            </label>
            @if($period === 'custom')
                <label class="text-sm">С <input type="date" wire:model.live.debounce.500ms="dateFrom" class="mt-1 block rounded-lg border-gray-300 bg-white text-sm dark:border-gray-700 dark:bg-gray-900" /></label>
                <label class="text-sm">По <input type="date" wire:model.live.debounce.500ms="dateTo" class="mt-1 block rounded-lg border-gray-300 bg-white text-sm dark:border-gray-700 dark:bg-gray-900" /></label>
            @else
                <span class="text-sm text-gray-500 pb-2">{{ $dateFrom }} — {{ $dateTo }}</span>
            @endif
            <span wire:loading class="text-sm text-primary-600 pb-2">Обновление…</span>
        </div>

        @php($stats = $this->stats)
        @if(isset($stats['invalid']))
            <p class="rounded-lg bg-red-50 p-4 text-sm text-red-700">Укажите корректный диапазон дат (не более трёх лет).</p>
        @else
            @php($money = fn ($value) => number_format((float) $value, 2, ',', ' ') . ' ₽')
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach([
                    ['Заказов', number_format($stats['orders_count'], 0, ',', ' ')],
                    ['Сумма заказов', $money($stats['order_total'])],
                    ['Оплаченных заказов', number_format($stats['paid_orders'], 0, ',', ' ')],
                    ['Сумма заказов со статусом «Оплачено»', $money($stats['paid_order_total'])],
                    ['Средний чек', $money($stats['average_check'])],
                    ['Скидки', $money($stats['discounts'])],
                    ['Новых покупателей', $stats['new_customers']],
                    ['Уникальных зарегистрированных покупателей', $stats['unique_customers']],
                ] as [$label, $value])
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                        <p class="text-xs text-gray-500">{{ $label }}</p>
                        <p class="mt-2 text-xl font-bold text-gray-950 dark:text-white">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-7 grid gap-6 lg:grid-cols-2">
                <div>
                    <h3 class="mb-3 font-semibold">Динамика заказов</h3>
                    @php($dailyCounts = array_column($stats['daily'] ?? [], 'count'))
                    @php($maxDaily = max(1, max($dailyCounts ?: [0])))
                    <div class="space-y-2 max-h-72 overflow-y-auto">
                        @forelse($stats['daily'] as $day)
                            <div class="grid grid-cols-[6rem_1fr_3rem] items-center gap-2 text-xs">
                                <span>{{ \Carbon\Carbon::parse($day['day'])->format('d.m.Y') }}</span>
                                <div class="h-3 overflow-hidden rounded bg-gray-100 dark:bg-gray-800"><div class="h-full rounded bg-primary-600" style="width: {{ 100 * $day['count'] / $maxDaily }}%"></div></div>
                                <span class="text-right">{{ $day['count'] }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Заказов за период нет.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h3 class="mb-3 font-semibold">Статусы заказов</h3>
                    <div class="space-y-2">
                        @forelse($stats['statuses'] as $item)
                            <div class="flex justify-between gap-3 border-b border-gray-100 py-2 text-sm dark:border-gray-800"><span>{{ $item['status'] }}</span><strong>{{ $item['count'] }}</strong></div>
                        @empty <p class="text-sm text-gray-500">Нет данных.</p> @endforelse
                    </div>
                    <h3 class="mb-3 mt-6 font-semibold">Статусы оплаты</h3>
                    <div class="space-y-2">
                        @foreach($stats['payments'] as $item)
                            <div class="flex justify-between gap-3 border-b border-gray-100 py-2 text-sm dark:border-gray-800"><span>{{ $item['status'] }} ({{ $item['count'] }})</span><strong>{{ $money($item['amount']) }}</strong></div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-7">
                <h3 class="mb-3 font-semibold">Популярные товары по количеству</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b dark:border-gray-700"><th class="py-2 pr-4">Товар</th><th class="py-2 pr-4">Артикул</th><th class="py-2 pr-4 text-right">Количество</th><th class="py-2 text-right">Сумма</th></tr></thead>
                        <tbody>
                        @forelse($stats['top_products'] as $item)
                            <tr class="border-b border-gray-100 dark:border-gray-800"><td class="py-2 pr-4">{{ $item['name'] }}</td><td class="py-2 pr-4">{{ $item['sku'] }}</td><td class="py-2 pr-4 text-right">{{ number_format($item['quantity'], 3, ',', ' ') }}</td><td class="py-2 text-right">{{ $money($item['amount']) }}</td></tr>
                        @empty <tr><td colspan="4" class="py-4 text-gray-500">Нет продаж за период.</td></tr> @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="mt-5 text-xs text-gray-500">«Оплачено» определяется по payment_status = paid. Данные обновляются с кэшем до 60 секунд. Себестоимость и прибыль не выводятся: в предоставленных таблицах нет расходов.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
