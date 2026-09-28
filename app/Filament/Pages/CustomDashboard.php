<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SystemInfo;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Page;

class CustomDashboard extends Page
{
    use HasFiltersAction;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Сводка';

    protected static ?string $title = 'Сводная информация';

    protected static ?int $navigationSort = 1;

    protected function getHeaderWidgets(): array
    {
        return [
            SystemInfo::class,
//            NavigationGroupsWidget::class,
//            OrdersWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
//        return 3;
        return [
            'md' => 3,
            'xl' => 4,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->schema([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                    // ...
                ]),
        ];
    }
}
