<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('number')
                    ->required(),
                TextInput::make('one_c_id')
                    ->default(null),
                TextInput::make('one_c_number')
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('new'),
                TextInput::make('payment_status')
                    ->required()
                    ->default('pending'),
                TextInput::make('currency')
                    ->required()
                    ->default('RUB'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('delivery_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Textarea::make('customer_data')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('delivery_data')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('payment_data')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('comment')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('paid_at'),
                TextInput::make('sync_status')
                    ->required()
                    ->default('pending'),
                Textarea::make('sync_error')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('synced_at'),
            ]);
    }
}
