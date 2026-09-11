<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductVariantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('SKU / Вариант')
                ->schema([
                    TextEntry::make('product.lang.name')->label('Товар'),
                    TextEntry::make('lang.name')->label('Название'),
                    TextEntry::make('sku')->label('Артикул'),
                    TextEntry::make('barcode')->label('Штрих-код'),
                    TextEntry::make('stock')->label('Остаток'),
                    IconEntry::make('is_active')->label('Активен')->boolean(),
                    TextEntry::make('optionValues.lang.value')->label('Опции')->badge()->separator(', '),
                    TextEntry::make('one_c_id')->label('ID 1С'),
                ])
                ->columns(3),
        ]);
    }
}
