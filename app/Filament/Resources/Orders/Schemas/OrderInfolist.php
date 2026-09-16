<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('uuid')
                    ->label('UUID'),
                TextEntry::make('number'),
                TextEntry::make('one_c_id')
                    ->placeholder('-'),
                TextEntry::make('one_c_number')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('payment_status'),
                TextEntry::make('currency'),
                TextEntry::make('subtotal')
                    ->numeric(),
                TextEntry::make('delivery_amount')
                    ->numeric(),
                TextEntry::make('discount_amount')
                    ->numeric(),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('customer_data')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('delivery_data')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('payment_data')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('comment')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('sync_status'),
                TextEntry::make('sync_error')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('synced_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
