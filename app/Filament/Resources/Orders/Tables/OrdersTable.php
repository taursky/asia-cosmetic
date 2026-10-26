<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('Заказ')
                    ->limit(20)
                    ->width(30)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (?string $state): string => self::orderColor($state))
                    ->searchable(),
                TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->badge()
                    ->color(fn (?string $state): string => self::paymentColor($state))
                    ->searchable(),
                TextColumn::make('delivery_data.status')
                    ->label('Доставка')
                    ->badge()
                    ->color(fn (?string $state): string => self::orderColor($state))
                    ->placeholder('Не указан')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label('Сумма')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Валюта')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sync_status')
                    ->label('1С')
                    ->badge()
                    ->color(fn (?string $state): string => self::syncColor($state))
                    ->toggleable(),
                TextColumn::make('one_c_number')
                    ->label('Номер 1С')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус заказа')->options([
                    'new' => 'Новый', 'awaiting_invoice' => 'Ожидает счёт',
                    'pending' => 'Ожидает', 'confirmed' => 'Подтверждён',
                    'processing' => 'В обработке', 'shipped' => 'Отправлен',
                    'completed' => 'Завершён', 'cancelled' => 'Отменён',
                ]),
                SelectFilter::make('payment_status')->label('Статус оплаты')->options([
                    'pending' => 'Ожидает', 'paid' => 'Оплачен',
                    'failed' => 'Ошибка', 'refunded' => 'Возврат',
                ]),
                // Статус доставки хранится в JSON delivery_data.status, отдельной колонки нет.
                SelectFilter::make('delivery_status')
                    ->label('Статус доставки')
                    ->options([
                        'pending' => 'Ожидает',
                        'processing' => 'Готовится',
                        'shipped' => 'Отправлен',
                        'in_delivery' => 'В пути',
                        'delivered' => 'Доставлен',
                        'cancelled' => 'Отменён',
                ])->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? $query->where('delivery_data->status', $data['value']) : $query),
                SelectFilter::make('sync_status')
                    ->label('Обмен с 1С')
                    ->options([
                        'pending' => 'Ожидает',
                        'synced' => 'Синхронизирован',
                        'failed' => 'Ошибка',
                ]),
                Filter::make('created_between')->label('Дата заказа')->schema([
                    DatePicker::make('from')->label('С'),
                    DatePicker::make('until')->label('По'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date))),
                Filter::make('not_synced')->label('Не синхронизированы')
                    ->query(fn (Builder $query): Builder => $query->whereIn('sync_status', ['pending', 'failed'])),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ]);
    }

    private static function orderColor(?string $status): string
    {
        return match ($status) {
            'new', 'pending', 'awaiting_invoice' => 'warning',
            'confirmed', 'processing', 'in_progress', 'assembled' => 'info',
            'shipped', 'in_delivery' => 'primary',
            'completed', 'delivered' => 'success',
            'cancelled', 'canceled', 'failed', 'returned' => 'danger',
            default => 'gray',
        };
    }

    private static function paymentColor(?string $status): string
    {
        return match ($status) {
            'paid', 'succeeded', 'refunded_partially' => 'success',
            'pending', 'awaiting_payment', 'awaiting_invoice' => 'warning',
            'refunded', 'failed', 'cancelled' => 'danger',
            default => 'gray',
        };
    }

    private static function syncColor(?string $status): string
    {
        return match ($status) {
            'synced' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            default => 'gray',
        };
    }
}
