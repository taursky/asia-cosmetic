<?php

namespace App\Filament\Resources\CustomerRoles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomerRolesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->label('ID')
                ->sortable(),
            TextColumn::make('name')
                ->label('Название')
                ->searchable()
                ->sortable(),
            TextColumn::make('code')
                ->label('Код')
                ->searchable()
                ->copyable(),
            TextColumn::make('users_count')
                ->counts('users')
                ->label('Покупателей')
                ->badge(),
            ToggleColumn::make('is_active')
                ->label('Активна')
                ->onColor('success')
                ->offColor('danger'),
            TextColumn::make('sort_order')
                ->label('Сортировка')
                ->sortable(),
        ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность'),
            ])
            ->recordActions([
                EditAction::make()
                    ->color('info')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                ])
            ])
            ->defaultSort('sort_order');
    }
}
