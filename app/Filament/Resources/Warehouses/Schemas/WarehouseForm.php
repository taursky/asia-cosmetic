<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Склад')->schema([
                TextInput::make('name')->label('Название')->required()->maxLength(255),
                TextInput::make('code')->label('Код')->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('one_c_id')->label('ID 1С')->maxLength(36)->unique(ignoreRecord: true),
                TextInput::make('external_id')->label('Внешний ID')->maxLength(255),
                TextInput::make('sort_order')->label('Сортировка')->numeric()->default(0),
                Toggle::make('is_active')->label('Активен')->default(true),
            ])
                ->columnSpanFull()
                ->columns(3),
        ]);
    }
}
