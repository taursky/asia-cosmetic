<?php

namespace App\Filament\Resources\AttributeValues\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttributeValueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Значение')->schema([
                TextEntry::make('attribute.lang.name')->label('Атрибут'),
                TextEntry::make('lang.value')->label('Значение'),
                TextEntry::make('code')->label('Код'),
                TextEntry::make('one_c_id')->label('ID 1С'),
            ])->columns(2),
        ]);
    }
}
