<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Название')->searchable()->sortable(),
                TextColumn::make('code')->label('Код')->searchable(),
                TextColumn::make('one_c_id')->label('ID 1С')->searchable()->toggleable(),
                TextColumn::make('stocks_count')->counts('stocks')->label('Позиций')->badge(),
                IconColumn::make('is_active')->label('Активен')->boolean(),
                TextColumn::make('synced_at')->label('Синхронизация')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Активность'),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
            ])
            ->defaultSort('sort_order');
    }
}
