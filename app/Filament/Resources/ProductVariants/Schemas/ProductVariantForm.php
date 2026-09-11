<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use App\Models\OptionValue;
use App\Models\ProductPriceType;
use App\Filament\Support\ImageUploadFields;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('variantTabs')
                ->columnSpanFull()
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Основное')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make('SKU / Вариант')
                                ->schema([
                                    Select::make('product_id')
                                        ->relationship('product', 'sku')
                                        ->label('Товар')
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    TextInput::make('sku')->label('Артикул SKU')->maxLength(255),
                                    TextInput::make('barcode')->label('Штрих-код')->maxLength(255),
                                    TextInput::make('external_id')->label('Внешний ID')->maxLength(255),
                                    TextInput::make('one_c_id')->label('ID 1С')->maxLength(36),
                                    Toggle::make('is_active')->label('Активен')->default(true),
                                    TextInput::make('stock')->label('Остаток')->numeric()->step(0.001)->default(0),
                                    TextInput::make('weight')->label('Вес')->numeric()->step(0.001),
                                    TextInput::make('length')->label('Длина')->numeric()->step(0.001),
                                    TextInput::make('width')->label('Ширина')->numeric()->step(0.001),
                                    TextInput::make('height')->label('Высота')->numeric()->step(0.001),
                                    TextInput::make('sort_order')->label('Сортировка')->numeric()->default(0)->required(),
                                ])
                                ->columns(4),
                        ]),

                    Tab::make('Переводы')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Repeater::make('langs')
                                ->relationship('langs')
                                ->label('Переводы SKU')
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

                                    TextInput::make('name')->label('Название')->maxLength(255),
                                    Textarea::make('value')->label('Значение')->rows(2),
                                    Textarea::make('description')->label('Описание')->rows(3)->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('Опции')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Select::make('optionValues')
                                ->relationship('optionValues', 'id')
                                ->label('Значения опций')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->getOptionLabelFromRecordUsing(
                                    fn (OptionValue $record): string =>
                                        ($record->option?->lang?->name ?? $record->option?->code ?? 'Опция')
                                        . ': '
                                        . ($record->lang?->value ?? $record->code ?? "#{$record->id}")
                                )
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Цены')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Repeater::make('prices')
                                ->relationship('prices')
                                ->label('Цены SKU')
                                ->addActionLabel('Добавить цену')
                                ->collapsible()
                                ->schema([
                                    Select::make('product_price_type_id')
                                        ->label('Тип цены')
                                        ->options(fn () => ProductPriceType::query()
                                            ->where('is_active', true)
                                            ->orderBy('sort_order')
                                            ->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    TextInput::make('amount')->label('Цена')->numeric()->step(0.01)->required(),
                                    TextInput::make('old_amount')->label('Старая цена')->numeric()->step(0.01),
                                    TextInput::make('currency')->label('Валюта')->default('RUB')->maxLength(3)->required(),
                                    TextInput::make('min_quantity')->label('Мин. количество')->numeric()->step(0.001)->default(1)->required(),
                                ])
                                ->columns(5),
                        ]),

                    Tab::make('Изображения')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Repeater::make('images')
                                ->relationship('images')
                                ->label('Изображения SKU')
                                ->addActionLabel('Добавить изображение')
                                ->orderColumn('position')
                                ->reorderable()
                                ->schema([
//                                    FileUpload::make('name')
//                                        ->label('Файл')
//                                        ->image()
//                                        ->disk('public')
//                                        ->directory('catalog/products/variants')
//                                        ->imageEditor()
//                                        ->required()
//                                        ->columnSpan(2),
//
//                                    Toggle::make('is_primary')->label('Основное'),
//                                    TextInput::make('position')->label('Позиция')->numeric()->default(1)->required(),
//                                    TextInput::make('mime_type')->label('MIME')->maxLength(255),
                                    ImageUploadFields::schema('catalog/products/variants')
                                ])
                                ->columns(3),
                        ]),
                ]),
        ]);
    }
}
