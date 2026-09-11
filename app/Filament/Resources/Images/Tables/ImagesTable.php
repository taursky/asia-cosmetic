<?php

namespace App\Filament\Resources\Images\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ImagesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('name')->label('Фото')->disk('public')->square(),
            TextColumn::make('imageable_type')->label('Тип')->formatStateUsing(fn (string $state) => class_basename($state))->badge(),
            TextColumn::make('imageable_id')->label('ID')->sortable(),
            TextColumn::make('position')->label('Позиция')->sortable(),
            IconColumn::make('is_primary')->label('Основное')->boolean(),
            TextColumn::make('mime_type')->label('MIME')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('imageable_type')->label('Тип')->options([
                \App\Models\Product::class => 'Товар',
                \App\Models\Option::class => 'Опция / SKU',
            ]),
        ])->recordActions([
            EditAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ])->defaultSort('position');
    }
}
