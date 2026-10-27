<x-filament-widgets::widget>
    <x-filament::section heading="Аналитика заказов" description="Финансовые и операционные показатели" collapsible>

        <div class="mt-6">
            {{-- Период --}}
            <div class="mb-6 flex flex-wrap items-end gap-3">
                <div class="min-w-40">
                    <label class="mb-1 block text-sm font-medium">Период</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="period">
                            <option value="today">Сегодня</option>
                            <option value="7days">7 дней</option>
                            <option value="month">Текущий месяц</option>
                            <option value="custom">Произвольный</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                @if ($period === 'custom')
                    <div>
                        <label class="mb-1 block text-sm font-medium">С</label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="date"
                                wire:model.live="dateFrom"
                            />
                        </x-filament::input.wrapper>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">По</label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="date"
                                wire:model.live="dateTo"
                            />
                        </x-filament::input.wrapper>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-6">
            <x-filament::section heading="Фин. Статистика" collapsible>

                {{-- KPI --}}
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
{{--                    @include(--}}
{{--                        'filament.admin.widgets.partials.order-stat',--}}
{{--                        [--}}
{{--                            'title' => 'Выручка',--}}
{{--                            'value' => number_format(--}}
{{--                                $this->stats['revenue'],--}}
{{--                                0,--}}
{{--                                ',',--}}
{{--                                ' '--}}
{{--                            ) . ' ¥',--}}
{{--                            'icon' => 'heroicon-o-banknotes',--}}
{{--                        ]--}}
{{--                    )--}}

{{--                    @include(--}}
{{--                        'filament.admin.widgets.partials.order-stat',--}}
{{--                        [--}}
{{--                            'title' => 'Профит',--}}
{{--                            'value' => number_format(--}}
{{--                                $this->stats['profit'],--}}
{{--                                0,--}}
{{--                                ',',--}}
{{--                                ' '--}}
{{--                            ) . ' ¥',--}}
{{--                            'icon' => 'heroicon-o-chart-bar-square',--}}
{{--                        ]--}}
{{--                    )--}}

{{--                    @include(--}}
{{--                        'filament.admin.widgets.partials.order-stat',--}}
{{--                        [--}}
{{--                            'title' => 'Себестоимость',--}}
{{--                            'value' => number_format(--}}
{{--                                $this->stats['purchase_cost'],--}}
{{--                                0,--}}
{{--                                ',',--}}
{{--                                ' '--}}
{{--                            ) . ' ¥',--}}
{{--                            'icon' => 'heroicon-o-shopping-cart',--}}
{{--                        ]--}}
{{--                    )--}}

{{--                    @include(--}}
{{--                        'filament.admin.widgets.partials.order-stat',--}}
{{--                        [--}}
{{--                            'title' => 'Расходы на доставку',--}}
{{--                            'value' => number_format(--}}
{{--                                $this->stats['shipping_expenses'],--}}
{{--                                0,--}}
{{--                                ',',--}}
{{--                                ' '--}}
{{--                            ) . ' ¥',--}}
{{--                            'icon' => 'heroicon-o-truck',--}}
{{--                        ]--}}
{{--                    )--}}

{{--                    @include(--}}
{{--                        'filament.admin.widgets.partials.order-stat',--}}
{{--                        [--}}
{{--                            'title' => 'Скидки',--}}
{{--                            'value' => number_format(--}}
{{--                                $this->stats['discounts'],--}}
{{--                                0,--}}
{{--                                ',',--}}
{{--                                ' '--}}
{{--                            ) . ' ¥',--}}
{{--                            'icon' => 'heroicon-o-receipt-percent',--}}
{{--                        ]--}}
{{--                    )--}}

{{--                    @include(--}}
{{--                        'filament.admin.widgets.partials.order-stat',--}}
{{--                        [--}}
{{--                            'title' => 'Новые клиенты',--}}
{{--                            'value' => number_format(--}}
{{--                                $this->stats['new_customers'],--}}
{{--                                0,--}}
{{--                                ',',--}}
{{--                                ' '--}}
{{--                            ),--}}
{{--                            'icon' => 'heroicon-o-user-plus',--}}
{{--                        ]--}}
{{--                    )--}}
                </div>
            </x-filament::section>
        </div>
        {{-- Скорость --}}
{{--        <div class="mt-6">--}}
{{--            <x-filament::section heading="Скорость выполнения" collapsible>--}}
{{--                <div class="grid gap-5 md:grid-cols-3">--}}
{{--                    <div>--}}
{{--                        <div class="text-sm text-gray-500">Средний срок</div>--}}

{{--                        <div class="text-2xl font-semibold">--}}
{{--                            {{ $this->stats['speed']['average_days'] }}--}}
{{--                            дней--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div>--}}
{{--                        <div class="text-sm text-gray-500">Выполнено в срок</div>--}}
{{--                        <div class="text-2xl font-semibold">--}}
{{--                            {{ $this->stats['speed']['on_time_percent'] }}%--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div>--}}
{{--                        <div class="text-sm text-gray-500">SLA</div>--}}
{{--                        <div class="text-2xl font-semibold">--}}
{{--                            {{ $this->stats['speed']['sla_days'] }}--}}
{{--                            дней--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </x-filament::section>--}}
{{--        </div>--}}

        {{-- Заказы --}}
{{--        <div class="mt-6">--}}
{{--            <x-filament::section heading="Заказы" description="Текущее состояние заказов" collapsible>--}}
{{--                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">--}}
{{--                    <div class="rounded-xl border p-4">--}}
{{--                        <div class="text-sm text-gray-500">--}}
{{--                            В обработке--}}
{{--                        </div>--}}

{{--                        <div class="mt-1 text-2xl font-bold">--}}
{{--                            {{ $this->stats['statuses']['processing'] }}--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="rounded-xl border p-4">--}}
{{--                        <div class="text-sm text-gray-500">--}}
{{--                            Готово к отгрузке--}}
{{--                        </div>--}}

{{--                        <div class="mt-1 text-2xl font-bold">--}}
{{--                            {{ $this->stats['statuses']['ready_to_ship'] }}--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="rounded-xl border p-4">--}}
{{--                        <div class="text-sm text-gray-500">--}}
{{--                            В пути--}}
{{--                        </div>--}}

{{--                        <div class="mt-1 text-2xl font-bold">--}}
{{--                            {{ $this->stats['statuses']['in_transit'] }}--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                    <div class="rounded-xl border p-4">--}}
{{--                        <div class="text-sm text-gray-500">--}}
{{--                            Готов к выдаче--}}
{{--                        </div>--}}

{{--                        <div class="mt-1 text-2xl font-bold">--}}
{{--                            {{ $this->stats['statuses']['ready_for_pickup'] }}--}}
{{--                        </div>--}}
{{--                    </div>--}}

{{--                </div>--}}

{{--            </x-filament::section>--}}

{{--        </div>--}}

        {{-- Проблемы --}}
{{--        <div class="mt-4">--}}
{{--            <x-filament::section heading="Проблемные заказы" description="Требуют внимания" collapsible>--}}
{{--                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">--}}
{{--                    --}}{{--todo проверить статусы--}}
{{--                    @foreach ([--}}
{{--                        'overdue' => 'Просроченные',--}}
{{--                        'abandoned' => 'Брошенные',--}}
{{--                        'delivery_delay' => 'Задержка доставки',--}}
{{--                        'awaiting_consultant' => 'Ожидают консультанта',--}}
{{--                    ] as $key => $label)--}}

{{--                        <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-900 dark:bg-danger-950">--}}
{{--                            <div class="text-sm text-danger-600">--}}
{{--                                {{ $label }}--}}
{{--                            </div>--}}
{{--                            <div class="mt-1 text-2xl font-bold text-danger-700">--}}
{{--                                {{ $this->stats['problems'][$key] }}--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    @endforeach--}}
{{--                </div>--}}
{{--            </x-filament::section>--}}
{{--        </div>--}}
    </x-filament::section>
</x-filament-widgets::widget>
