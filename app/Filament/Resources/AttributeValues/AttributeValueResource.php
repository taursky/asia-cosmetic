<?php

namespace App\Filament\Resources\AttributeValues;

use App\Filament\Resources\AttributeValues\Pages\CreateAttributeValue;
use App\Filament\Resources\AttributeValues\Pages\EditAttributeValue;
use App\Filament\Resources\AttributeValues\Pages\ListAttributeValues;
use App\Filament\Resources\AttributeValues\Pages\ViewAttributeValue;
use App\Filament\Resources\AttributeValues\Schemas\AttributeValueForm;
use App\Filament\Resources\AttributeValues\Schemas\AttributeValueInfolist;
use App\Filament\Resources\AttributeValues\Tables\AttributeValuesTable;
use App\Models\AttributeValue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttributeValueResource extends Resource
{
    protected static ?string $model = AttributeValue::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;
    protected static string|\UnitEnum|null $navigationGroup = 'Продукты';
    protected static ?string $navigationLabel = 'Значения атрибутов';
    protected static ?string $modelLabel = 'Значение атрибута';
    protected static ?string $pluralModelLabel = 'Значения атрибутов';
    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema { return AttributeValueForm::configure($schema); }
    public static function infolist(Schema $schema): Schema { return AttributeValueInfolist::configure($schema); }
    public static function table(Table $table): Table { return AttributeValuesTable::configure($table); }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributeValues::route('/'),
            'create' => CreateAttributeValue::route('/create'),
            'view' => ViewAttributeValue::route('/{record}'),
            'edit' => EditAttributeValue::route('/{record}/edit'),
        ];
    }
}
