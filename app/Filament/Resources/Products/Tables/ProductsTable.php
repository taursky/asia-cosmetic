<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use App\Filament\Support\ProductImportExportActions;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lang.name')->label('Название')->searchable()->sortable(),
                TextColumn::make('sku')->label('Артикул')->searchable()->copyable(),
                TextColumn::make('one_c_id')->label('ID 1С')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('variants_count')->counts('variants')->label('SKU')->badge(),
                IconColumn::make('is_active')->label('Активен')->boolean(),
                IconColumn::make('is_visible')->label('На сайте')->boolean(),
                TextColumn::make('updated_at')->label('Обновлён')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Активность'),
                TernaryFilter::make('is_visible')->label('Видимость'),
                TrashedFilter::make()->label('Удалённые'),
            ])
            ->recordActions([

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                ...ProductImportExportActions::actions(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
