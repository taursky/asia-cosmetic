<?php

namespace App\Filament\Resources\CatalogCollections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CatalogCollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->label('Название')
                ->searchable()
                ->sortable(),
            TextColumn::make('slug')->label('Slug')->copyable()->searchable(),
            TextColumn::make('filter')->label('Фильтр')->badge(),
            ToggleColumn::make('is_active')
                ->label('Активна')
                ->onColor('success')
                ->offColor('danger'),
            ToggleColumn::make('show_in_menu')
                ->label('Меню')
                ->onColor('info')
                ->offColor('danger'),
            ToggleColumn::make('show_on_home')
                ->label('Главная')
                ->onColor('info')
                ->offColor('warning'),
            TextColumn::make('sort_order')->label('Порядок')->sortable(),
        ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                ->color('info'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                ]),
            ]);
    }
}
