<?php

namespace App\Filament\Resources\ProductPriceTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductPriceTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Название')->searchable()->sortable(),
            TextColumn::make('code')->label('Код')->badge()->searchable(),
            TextColumn::make('prices_count')->counts('prices')->label('Цен')->badge(),
            IconColumn::make('is_active')->label('Активен')->boolean(),
            TextColumn::make('sort_order')->label('Порядок')->sortable(),
        ])->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ])->defaultSort('sort_order');
    }
}
