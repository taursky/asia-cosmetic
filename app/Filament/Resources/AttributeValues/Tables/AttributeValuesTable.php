<?php

namespace App\Filament\Resources\AttributeValues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttributeValuesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('attribute.lang.name')->label('Атрибут')->searchable(),
            TextColumn::make('lang.value')->label('Значение')->searchable(),
            TextColumn::make('code')->label('Код')->searchable()->toggleable(),
            TextColumn::make('numeric_value')->label('Число')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('sort_order')->label('Порядок')->sortable(),
        ])->filters([
            SelectFilter::make('attribute_id')->relationship('attribute', 'code')->label('Атрибут')->searchable()->preload(),
        ])->recordActions([
            ViewAction::make(), EditAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ])->defaultSort('sort_order');
    }
}
