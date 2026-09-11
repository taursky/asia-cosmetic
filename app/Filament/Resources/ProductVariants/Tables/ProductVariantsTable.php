<?php

namespace App\Filament\Resources\ProductVariants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductVariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.lang.name')->label('Товар')->searchable()->sortable(),
                TextColumn::make('lang.name')->label('Название варианта')->searchable(),
                TextColumn::make('sku')->label('SKU')->searchable()->copyable()->sortable(),
                TextColumn::make('barcode')->label('Штрих-код')->searchable()->toggleable(),
                TextColumn::make('stock')->label('Остаток')->numeric(decimalPlaces: 3)->sortable(),
                TextColumn::make('optionValues.lang.value')
                    ->label('Опции')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->toggleable(),
                IconColumn::make('is_active')->label('Активен')->boolean(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()->label('Удалённые'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('product_id');
    }
}
