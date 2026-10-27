<?php

namespace App\Filament\Resources\CatalogCollections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CatalogCollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основное')->schema([
                    TextInput::make('title')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($state, $set) => $set('slug', Str::slug((string)$state))),
                    TextInput::make('slug')
                        ->label('URL (slug)')
                        ->required()
                        ->maxLength(120)
                        ->regex('/^[a-z0-9]+(?:[a-z0-9_-]*[a-z0-9])?$/')
                        ->unique(ignoreRecord: true)
                        ->helperText('Например: summer-sale; адрес /collections/summer-sale'),
                    Textarea::make('description')
                        ->label('Описание')
                        ->rows(3),
                    Toggle::make('is_active')
                        ->label('Опубликована')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(true),
                    Toggle::make('show_in_menu')
                        ->label('Показывать в меню каталога')
                        ->onColor('info')
                        ->offColor('danger'),
                    Toggle::make('show_on_home')
                        ->label('Показывать на главной')
                        ->onColor('info')
                        ->offColor('danger'),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()->integer()
                        ->minValue(0)
                        ->default(100),
                ])->columns(2),
                Section::make('Правила отбора')->schema([
                    Select::make('filter')
                        ->label('Фильтр')
                        ->required()
                        ->default('all')
                        ->options([
                            'all' => 'Все товары',
                            'sale' => 'Акции (old_amount > amount)',
                            'hit' => 'Хиты продаж',
                            'rated' => 'С отзывами',
                            'new' => 'Новинки за N дней',
                            'manual' => 'Товары вручную',
                        ])->live(),
                    Select::make('sort')
                        ->label('Сортировка')
                        ->required()
                        ->default('new')
                        ->options([
                            'new' => 'Сначала новые', 'old' => 'Сначала старые',
                            'popular' => 'По популярности', 'rating' => 'По рейтингу',
                        ]),
                    Select::make('category_id')
                        ->label('Ограничить категорией')
                        ->relationship('category', 'id')
                        ->getOptionLabelFromRecordUsing(fn($record) => $record->lang?->name ?? ('#' . $record->id))
                        ->searchable()->preload()->nullable(),
                    TextInput::make('days')->label('За последние N дней')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(3650)
                        ->visible(fn($get) => $get('filter') === 'new')
                        ->required(fn($get) => $get('filter') === 'new'),
                    Select::make('products')
                        ->label('Товары в ручной подборке')
                        ->relationship('products', 'sku')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Используется только при фильтре «Товары вручную».')
                        ->visible(fn($get) => $get('filter') === 'manual'),
                ])->columns(2),
                Section::make('SEO')->schema([
                    TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                    Textarea::make('seo_description')->label('SEO description')->rows(2),
                ]),
            ])->columns(3);
    }
}
