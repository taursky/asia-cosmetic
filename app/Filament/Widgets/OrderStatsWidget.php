<?php

namespace App\Filament\Widgets;

use App\Services\Analytics\OrderAnalyticsService;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Validation\ValidationException;

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
        $this->setPresetDates();
    }

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->setPresetDates();
        }
    }

    private function setPresetDates(): void
    {
        $today = CarbonImmutable::today();
        [$from, $to] = match ($this->period) {
            'today' => [$today, $today],
            '7days' => [$today->subDays(6), $today],
            '30days' => [$today->subDays(29), $today],
            'previous_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'year' => [$today->startOfYear(), $today],
            default => [$today->startOfMonth(), $today],
        };
        $this->dateFrom = $from->toDateString();
        $this->dateTo = $to->toDateString();
    }

    public function getStatsProperty(): array
    {
        try {
            $from = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->dateFrom);
            $to = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->dateTo);
            if (! $from || ! $to || $from->toDateString() !== $this->dateFrom || $to->toDateString() !== $this->dateTo || $from->gt($to)) {
                return ['invalid' => true];
            }
            if ($from->diffInDays($to) > 1096) {
                return ['invalid' => true];
            }
            return app(OrderAnalyticsService::class)->get($from, $to);
        } catch (\Throwable $e) {
            // Invalid user date should not crash the dashboard; database errors must still surface.
            if ($e instanceof \InvalidArgumentException) {
                return ['invalid' => true];
            }
            throw $e;
        }
    }
}
