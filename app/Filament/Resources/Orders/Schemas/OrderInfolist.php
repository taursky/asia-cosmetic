<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('viewOrderTabs')->columnSpanFull()->tabs([
                Tab::make('Заказ')->schema([
                    Section::make('Информация')->columns(3)->schema([
                        TextEntry::make('number')->label('Номер'),
                        TextEntry::make('status')->label('Статус')->badge(),
                        TextEntry::make('payment_status')->label('Оплата')->badge(),
                        TextEntry::make('subtotal')->label('Товары')->numeric(decimalPlaces: 2),
                        TextEntry::make('delivery_amount')->label('Доставка')->numeric(decimalPlaces: 2),
                        TextEntry::make('discount_amount')->label('Скидка')->numeric(decimalPlaces: 2),
                        TextEntry::make('total')->label('Итого')->numeric(decimalPlaces: 2),
                        TextEntry::make('currency')->label('Валюта'),
                        TextEntry::make('created_at')->label('Создан')->dateTime('d.m.Y H:i'),
                        TextEntry::make('comment')->label('Комментарий')->columnSpanFull()->placeholder('—'),
                    ]),
                ]),
                Tab::make('Товары')->schema([
                    Section::make('Состав заказа')->schema([
                        TextEntry::make('items_count')->label('Количество строк заказа')
                            ->state(fn ($record) => $record->items->count()),
                        TextEntry::make('items_json')->label('Строки заказа (JSON)')
                            ->state(fn ($record) => $record->items->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))
                            ->copyable()->columnSpanFull(),
                    ]),
                ]),
                Tab::make('Покупатель')->schema([self::json('customer_data', 'Данные покупателя')]),
                Tab::make('Доставка')->schema([self::json('delivery_data', 'Доставка')]),
                Tab::make('Оплата')->schema([
                    TextEntry::make('paid_at')->label('Дата оплаты')->dateTime('d.m.Y H:i')->placeholder('—'),
                    self::json('payment_data', 'Данные оплаты'),
                ]),
                Tab::make('1С')->schema([
                    TextEntry::make('uuid')->label('UUID'),
                    TextEntry::make('one_c_id')->label('ID 1С')->placeholder('—'),
                    TextEntry::make('one_c_number')->label('Номер 1С')->placeholder('—'),
                    TextEntry::make('sync_status')->label('Статус обмена')->badge(),
                    TextEntry::make('synced_at')->label('Дата обмена')->dateTime('d.m.Y H:i')->placeholder('—'),
                    TextEntry::make('sync_error')->label('Ошибка')->placeholder('—'),
                ]),
            ]),
        ]);
    }

    private static function json(string $field, string $label): TextEntry
    {
        return TextEntry::make($field)->label($label)
            ->formatStateUsing(fn ($state) => is_array($state)
                ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : ($state ?: '—'))
            ->columnSpanFull();
    }
}
