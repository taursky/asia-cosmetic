<?php

namespace App\Filament\Resources\ProductOptions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductOptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Опция')
                ->schema([
                    TextEntry::make('lang.name')->label('Название'),
                    TextEntry::make('code')->label('Код'),
                    TextEntry::make('values_count')->state(fn ($record) => $record->values()->count())->label('Количество значений'),
                    IconEntry::make('is_active')->label('Активна')->boolean(),
                    TextEntry::make('sort_order')->label('Порядок'),
                    TextEntry::make('one_c_id')->label('ID 1С'),
                ])
                ->columns(3),
        ]);
    }
}
