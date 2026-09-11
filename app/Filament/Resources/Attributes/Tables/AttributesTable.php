<?php

namespace App\Filament\Resources\Attributes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('lang.name')->label('Название')->searchable(),
            TextColumn::make('code')->label('Код')->searchable()->sortable()->badge(),
            TextColumn::make('type')->label('Тип')->badge(),
            IconColumn::make('is_filterable')->label('Фильтр')->boolean(),
            IconColumn::make('is_variant')->label('SKU')->boolean(),
            IconColumn::make('is_searchable')->label('Поиск')->boolean(),
            TextColumn::make('sort_order')->label('Порядок')->sortable(),
        ])->filters([
            TernaryFilter::make('is_filterable')->label('В фильтрах'),
            TernaryFilter::make('is_variant')->label('SKU-атрибуты'),
        ])->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ])->defaultSort('sort_order');
    }
}
