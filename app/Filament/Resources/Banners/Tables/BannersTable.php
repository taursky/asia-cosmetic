<?php

namespace App\Filament\Resources\Banners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('desktop_image')
                    ->label('Компьютер')
                    ->disk('public')
                    ->height(65)
                    ->width(130),
                ImageColumn::make('mobile_image')
                    ->label('Телефон')
                    ->disk('public')
                    ->imageHeight(65)
                    ->width(65),
                TextColumn::make('link')
                    ->label('Ссылка')
                    ->limit(45)
                    ->placeholder('Без ссылки')
                    ->copyable(),
                TextColumn::make('position')
                    ->label('Порядок')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Активен'),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Активность'),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
