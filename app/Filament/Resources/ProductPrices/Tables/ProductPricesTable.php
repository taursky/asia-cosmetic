<?php

namespace App\Filament\Resources\ProductPrices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('product.lang.name')->label('Товар')->searchable(),
            TextColumn::make('option.sku')->label('SKU')->placeholder('—')->searchable(),
            TextColumn::make('priceType.name')->label('Тип цены')->badge()->sortable(),
            TextColumn::make('amount')->label('Цена')->money(fn ($record) => $record->currency)->sortable(),
            TextColumn::make('old_amount')->label('Старая')->money(fn ($record) => $record->currency)->toggleable(),
            TextColumn::make('min_quantity')->label('Мин. кол-во')->numeric(decimalPlaces: 3),
            TextColumn::make('updated_at')->label('Обновлена')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('product_price_type_id')->relationship('priceType', 'name')->label('Тип цены')->preload(),
            SelectFilter::make('product_id')->relationship('product', 'sku')->label('Товар')->searchable()->preload(),
        ])->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ])->defaultSort('updated_at', 'desc');
    }
}
