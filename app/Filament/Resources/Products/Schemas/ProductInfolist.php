<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Товар')->schema([
                TextEntry::make('lang.name')->label('Название'),
                TextEntry::make('sku')->label('Артикул'),
                TextEntry::make('one_c_id')->label('ID 1С'),
                IconEntry::make('is_active')->label('Активен')->boolean(),
                IconEntry::make('is_visible')->label('На сайте')->boolean(),
                TextEntry::make('updated_at')->label('Обновлён')->dateTime('d.m.Y H:i'),
            ])->columns(3),
        ]);
    }
}
