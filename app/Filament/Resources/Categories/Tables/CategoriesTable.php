<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'asc')
            ->columns([
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('lang.name')
                ->label('Название')
                ->searchable(),
            TextColumn::make('parent.lang.name')
                ->label('Родитель')
                ->placeholder('—'),
            TextColumn::make('products_count')
                ->counts('products')
                ->label('Товаров')
                ->badge(),
            ToggleColumn::make('is_active')
                ->label('Активна')
                ->onColor('success')
                ->offColor('danger'),
            TextColumn::make('sort_order')
                ->label('Порядок')
                ->sortable(),
        ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->boolean()
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные')
                    ->native(false),

//                SelectFilter::make('level_depth')
//                    ->label('Уровень вложенности')
//                    ->options([
//                        0 => 'Корневые (0)',
//                        1 => '1 уровень',
//                        2 => '2 уровень',
//                        3 => '3 уровень',
//                        4 => '4 уровень',
//                    ]),
            ])->recordActions([
            EditAction::make()->iconButton(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ]);
    }
}
