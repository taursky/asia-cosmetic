<?php

namespace App\Filament\Support\B2B;

use App\Models\ProductPriceType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class CustomerRoleB2BFields
{
    public static function schema(): array
    {
        return [
            Section::make('Коммерческие условия')
                ->schema([
                    Select::make('product_price_type_id')
                        ->label('Тип цены')
                        ->options(fn () => ProductPriceType::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                        ->searchable()->preload()->required(),
                    TextInput::make('level')->label('Уровень')->numeric()->required(),
                    TextInput::make('order_threshold_amount')->label('Порог одного заказа')->numeric()->step(0.01),
                    TextInput::make('qualification_amount')->label('Оборот для роли')->numeric()->step(0.01)->required(),
                    TextInput::make('qualification_period_days')->label('Период оборота, дней')->numeric()->default(180)->required(),
                    TextInput::make('validity_days')->label('Срок роли, дней')->numeric()->default(180)->required(),
                    Toggle::make('is_default')->label('Роль по умолчанию'),
                    Toggle::make('is_auto')->label('Автоматический пересчёт')->default(true),
                ])->columns(4),
        ];
    }
}
