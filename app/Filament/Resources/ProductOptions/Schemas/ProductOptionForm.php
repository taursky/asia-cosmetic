<?php

namespace App\Filament\Resources\ProductOptions\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('optionTabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Основное')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Section::make('Опция')
                                ->schema([
                                    TextInput::make('code')
                                        ->label('Код')
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true),

                                    TextInput::make('external_id')
                                        ->label('Внешний ID')
                                        ->maxLength(255),

                                    TextInput::make('one_c_id')
                                        ->label('ID 1С')
                                        ->maxLength(36),

                                    TextInput::make('sort_order')
                                        ->label('Сортировка')
                                        ->numeric()
                                        ->default(0)
                                        ->required(),

                                    Toggle::make('is_active')
                                        ->label('Активна')
                                        ->default(true),
                                ])
                                ->columns(3),
                        ]),

                    Tab::make('Переводы')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Repeater::make('langs')
                                ->relationship('langs')
                                ->label('Переводы')
                                ->defaultItems(1)
                                ->addActionLabel('Добавить язык')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? $state['lang'] ?? null)
                                ->schema([
                                    Select::make('lang')
                                        ->label('Язык')
                                        ->options([
                                            'ru' => 'Русский',
                                            'en' => 'English',
                                            'zh' => '中文',
                                        ])
                                        ->required(),

                                    TextInput::make('name')
                                        ->label('Название')
                                        ->required()
                                        ->maxLength(255),

                                    Textarea::make('description')
                                        ->label('Описание')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('Значения')
                        ->icon('heroicon-o-list-bullet')
                        ->schema([
                            Repeater::make('values')
                                ->relationship('values')
                                ->label('Значения опции')
                                ->addActionLabel('Добавить значение')
                                ->orderColumn('sort_order')
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['code'] ?? null)
                                ->schema([
                                    TextInput::make('code')
                                        ->label('Код')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('external_id')
                                        ->label('Внешний ID')
                                        ->maxLength(255),

                                    TextInput::make('one_c_id')
                                        ->label('ID 1С')
                                        ->maxLength(36),

                                    TextInput::make('sort_order')
                                        ->label('Сортировка')
                                        ->numeric()
                                        ->default(0)
                                        ->required(),

                                    Toggle::make('is_active')
                                        ->label('Активно')
                                        ->default(true),

                                    Repeater::make('langs')
                                        ->relationship('langs')
                                        ->label('Переводы значения')
                                        ->defaultItems(1)
                                        ->addActionLabel('Добавить язык')
                                        ->collapsible()
                                        ->schema([
                                            Select::make('lang')
                                                ->label('Язык')
                                                ->options([
                                                    'ru' => 'Русский',
                                                    'en' => 'English',
                                                    'zh' => '中文',
                                                ])
                                                ->required(),

                                            TextInput::make('value')
                                                ->label('Значение')
                                                ->required()
                                                ->maxLength(255),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }
}
