<?php

namespace App\Filament\Resources\CustomerRoles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomerRolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
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

                TextColumn::make('priceType.name')
                    ->label('Тип цены')
                    ->placeholder('Не назначен')
                    ->badge()
                    ->sortable(),

                TextColumn::make('level')
                    ->label('Уровень')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('order_threshold_amount')
                    ->label('Порог заказа')
                    ->money('RUB')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('qualification_amount')
                    ->label('Оборот для роли')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('qualification_period_days')
                    ->label('Период')
                    ->suffix(' дн.')
                    ->sortable(),

                TextColumn::make('validity_days')
                    ->label('Срок роли')
                    ->suffix(' дн.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('current_users_count')
                    ->counts('currentUsers')
                    ->label('Текущих покупателей')
                    ->badge(),

                IconColumn::make('is_default')
                    ->label('По умолчанию')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_auto')
                    ->label('Авто')
                    ->boolean()
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Активна')
                    ->onColor('success')
                    ->offColor('danger'),

                TextColumn::make('sort_order')
                    ->label('Сортировка')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активность'),

                TernaryFilter::make('is_default')
                    ->label('По умолчанию'),

                TernaryFilter::make('is_auto')
                    ->label('Автоматический пересчёт'),

                SelectFilter::make('product_price_type_id')
                    ->label('Тип цены')
                    ->relationship('priceType', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->color('info')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('level');
    }
}
