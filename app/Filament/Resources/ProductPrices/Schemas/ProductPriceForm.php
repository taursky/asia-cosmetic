<?php

namespace App\Filament\Resources\ProductPrices\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Цена')->schema([
                Select::make('product_id')->relationship('product', 'sku')->label('Товар')->searchable()->preload()->required(),
                Select::make('option_id')->relationship('option', 'sku')->label('Опция / SKU')->searchable()->preload()->placeholder('Базовая цена товара'),
                Select::make('product_price_type_id')->relationship('priceType', 'name')->label('Тип цены')->searchable()->preload()->required(),
                TextInput::make('amount')->label('Цена')->numeric()->step(0.01)->required(),
                TextInput::make('old_amount')->label('Старая цена')->numeric()->step(0.01),
                TextInput::make('currency')->label('Валюта')->default('RUB')->maxLength(3)->required(),
                TextInput::make('min_quantity')->label('Мин. количество')->numeric()->step(0.001)->default(1)->required(),
                TextInput::make('external_id')->label('Внешний ID')->maxLength(255),
                TextInput::make('one_c_id')->label('ID 1С')->maxLength(36),
                TextInput::make('valid_from')->label('Действует с'),
                TextInput::make('valid_until')->label('Действует до'),
                TextInput::make('synced_at')->label('Синхронизировано')->disabled()->dehydrated(false),
            ])
                ->columnSpanFull()
                ->columns(3),
        ]);
    }
}
