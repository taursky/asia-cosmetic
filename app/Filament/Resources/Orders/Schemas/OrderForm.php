<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('orderTabs')->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Заказ')->schema([
                    Section::make('Обработка заказа')
                        ->columns(3)
                        ->schema([
                            TextInput::make('number')
                                ->label('Номер')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('status')
                                ->label('Статус заказа')
                                ->required()
                                ->maxLength(100)
                                ->datalist(['new', 'awaiting_invoice', 'pending', 'confirmed', 'processing', 'shipped', 'completed', 'cancelled'])
                                ->helperText('Можно выбрать подсказку или ввести статус из вашей интеграции 1С'),
                            TextInput::make('currency')
                                ->label('Валюта')
                                ->required()
                                ->default('RUB')
                                ->maxLength(3),
                            Textarea::make('comment')
                                ->label('Комментарий')
                                ->rows(4)
                                ->columnSpanFull(),
                    ]),
                    Section::make('Стоимость')->columns(4)->schema([
                        self::money('subtotal', 'Товары'),
                        self::money('delivery_amount', 'Доставка'),
                        self::money('discount_amount', 'Скидка'),
                        self::money('total', 'Итого'),
                    ]),
                ]),
                Tab::make('Покупатель')->schema([
                    Section::make('Данные покупателя на момент заказа')
                        ->description('Это снимок данных заказа, а не профиль пользователя.')
                        ->schema([
                            self::json('customer_data', 'Данные покупателя (JSON)'),
                    ]),
                ]),
                Tab::make('Доставка')->schema([
                    Section::make('Данные доставки')->schema([
                        self::json('delivery_data', 'Доставка (JSON)'),
                    ]),
                ]),
                Tab::make('Оплата')->schema([
                    Section::make('Состояние оплаты')
                        ->columns(2)
                        ->schema([
                            TextInput::make('payment_status')
                                ->label('Статус оплаты')
                                ->required()
                                ->maxLength(100)
                                ->datalist(['pending', 'paid', 'failed', 'refunded'])
                                ->helperText('Ручное изменение статуса не подтверждает платёж у провайдера'),
                            DateTimePicker::make('paid_at')->label('Дата оплаты')->seconds(false),
                            self::json('payment_data', 'Данные оплаты (JSON)'),
                    ]),
                ]),
                Tab::make('1С и синхронизация')->schema([
                    Section::make('Идентификаторы')->columns(2)->schema([
                        TextInput::make('uuid')->label('UUID')->disabled()->dehydrated(false),
                        TextInput::make('one_c_id')->label('ID в 1С')->maxLength(255),
                        TextInput::make('one_c_number')->label('Номер в 1С')->maxLength(255),
                        TextInput::make('sync_status')->label('Статус обмена')->required()->maxLength(100),
                        DateTimePicker::make('synced_at')->label('Синхронизирован')->disabled()->dehydrated(false),
                        Textarea::make('sync_error')->label('Ошибка синхронизации')->rows(5)->disabled()->dehydrated(false)->columnSpanFull(),
                    ]),
                ]),
            ]),
        ]);
    }

    private static function money(string $name, string $label): TextInput
    {
        return TextInput::make($name)->label($label)->numeric()->minValue(0)->required()->default(0)->step(0.01);
    }

    private static function json(string $name, string $label): Textarea
    {
        return Textarea::make($name)->label($label)->rows(12)->columnSpanFull()
            ->formatStateUsing(fn ($state): string => is_array($state)
                ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : (is_string($state) ? $state : '{}'))
            ->rules(['json'])
            ->dehydrateStateUsing(fn ($state): ?array => filled($state) ? json_decode($state, true) : null);
    }
}
