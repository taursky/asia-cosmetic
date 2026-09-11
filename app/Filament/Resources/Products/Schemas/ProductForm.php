<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPriceType;
use App\Models\OptionValue;
use App\Filament\Support\ImageUploadFields;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('productTabs')
                ->columnSpanFull()
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Основное')
                        ->icon('heroicon-o-information-circle')
                        ->schema(self::mainTab()),

                    Tab::make('Переводы')
                        ->icon('heroicon-o-language')
                        ->schema(self::langsTab()),

                    Tab::make('Цены')
                        ->icon('heroicon-o-banknotes')
                        ->schema(self::pricesTab()),

                    Tab::make('Атрибуты')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema(self::attributesTab()),

                    Tab::make('Опции / SKU')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema(self::optionsTab()),

                    Tab::make('Изображения')
                        ->icon('heroicon-o-photo')
                        ->schema(self::imagesTab()),

                    Tab::make('Категории')
                        ->icon('heroicon-o-folder')
                        ->schema(self::categoriesTab()),
                ]),
        ]);
    }

    private static function mainTab(): array
    {
        return [
            Section::make('Идентификация')
                ->schema([
                    TextInput::make('sku')->label('Артикул')->maxLength(255),
                    TextInput::make('external_id')->label('Внешний ID')->maxLength(255),
                    TextInput::make('one_c_id')->label('ID 1С')->maxLength(36),
                    TextInput::make('source')->label('Источник')->maxLength(255),
                    TextInput::make('source_url')->label('URL источника')->url()->columnSpanFull(),
                ])->columns(3),

            Section::make('Статус')
                ->schema([
                    Toggle::make('is_active')->label('Активен')->default(true),
                    Toggle::make('is_visible')->label('Показывать на сайте')->default(true),
                    Toggle::make('allow_discounts')->label('Разрешить скидки')->default(true),
                ])->columns(3),

            Section::make('Характеристики')
                ->schema([
                    TextInput::make('unit')->label('Единица измерения')->maxLength(32),
                    TextInput::make('vat_rate')->label('НДС, %')->numeric()->step(0.01),
                    TextInput::make('weight')->label('Вес')->numeric()->step(0.001),
                    TextInput::make('length')->label('Длина')->numeric()->step(0.001),
                    TextInput::make('width')->label('Ширина')->numeric()->step(0.001),
                    TextInput::make('height')->label('Высота')->numeric()->step(0.001),
                    TextInput::make('rating')->label('Рейтинг')->numeric()->step(0.01)->minValue(0)->maxValue(5),
                    TextInput::make('reviews_count')->label('Количество отзывов')->numeric()->minValue(0)->default(0),
                    TextInput::make('video_url')->label('Видео URL')->url()->columnSpanFull(),
                ])->columns(4),

            Section::make('Синхронизация')
                ->collapsed()
                ->schema([
                    TextInput::make('sync_hash')->label('Sync hash')->maxLength(64),
                    TextInput::make('synced_at')->label('Последняя синхронизация')->disabled()->dehydrated(false),
                ])->columns(2),
        ];
    }

    private static function langsTab(): array
    {
        return [
            Repeater::make('langs')
                ->relationship('langs')
                ->label('Языковые версии')
                ->defaultItems(1)
                ->addActionLabel('Добавить язык')
                ->itemLabel(fn (array $state): ?string => $state['lang'] ?? null)
                ->collapsible()
                ->schema([
                    Select::make('lang')
                        ->label('Язык')
                        ->options(self::locales())
                        ->required(),
                    TextInput::make('name')->label('Название')->required()->maxLength(255),
                    TextInput::make('slug')->label('Slug')->required()->maxLength(255),
                    Textarea::make('short_description')->label('Краткое описание')->rows(3)->columnSpanFull(),
                    Textarea::make('description')->label('Описание')->rows(8)->columnSpanFull(),
                    TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                    Textarea::make('seo_keywords')->label('SEO keywords')->rows(2),
                    Textarea::make('seo_description')->label('SEO description')->rows(3)->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    private static function pricesTab(): array
    {
        return [
            Repeater::make('prices')
                ->relationship('prices')
                ->label('Цены')
                ->addActionLabel('Добавить цену')
                ->defaultItems(0)
                ->schema([
                    Select::make('product_price_type_id')
                        ->label('Тип цены')
                        ->options(fn () => ProductPriceType::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                        ->searchable()->preload()->required(),
                    Select::make('product_variant_id')
                        ->label('Опция / SKU')
                        ->options(function ($livewire): array {
                            /** @var Product|null $product */
                            $product = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
                            return $product?->variants()->orderBy('sort_order')->pluck('sku', 'id')->all() ?? [];
                        })
                        ->searchable()
                        ->placeholder('Базовая цена товара'),
                    TextInput::make('amount')->label('Цена')->numeric()->step(0.01)->required(),
                    TextInput::make('old_amount')->label('Старая цена')->numeric()->step(0.01),
                    TextInput::make('currency')->label('Валюта')->default('RUB')->maxLength(3)->required(),
                    TextInput::make('min_quantity')->label('Мин. кол-во')->numeric()->step(0.001)->default(1)->required(),
                    TextInput::make('external_id')->label('Внешний ID')->maxLength(255),
                    TextInput::make('one_c_id')->label('ID 1С')->maxLength(36),
                ])
                ->collapsible()
                ->columnSpanFull(),
        ];
    }

    private static function attributesTab(): array
    {
        return [
            Select::make('attributeValues')
                ->relationship('attributeValues', 'id')
                ->label('Значения атрибутов')
                ->multiple()
                ->searchable()
                ->preload()
                ->getOptionLabelFromRecordUsing(fn (AttributeValue $record): string => trim(($record->attribute?->lang?->name ?? $record->attribute?->code ?? 'Атрибут') . ': ' . ($record->lang?->value ?? $record->code ?? "#{$record->id}")))
                ->columnSpanFull(),
        ];
    }

    private static function optionsTab(): array
    {
        return [
            Section::make('Доступные опции товара')
                ->description('Какие значения опций могут использоваться у вариантов этого товара.')
                ->schema([
                    Select::make('optionValues')
                        ->relationship('optionValues', 'id')
                        ->label('Значения опций')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn (OptionValue $record): string => trim(
                            ($record->option?->lang?->name ?? $record->option?->code ?? 'Опция')
                            . ': ' .
                            ($record->lang?->value ?? $record->code ?? "#{$record->id}")
                        ))
                        ->columnSpanFull(),
                ]),

            Repeater::make('variants')
                ->relationship('variants')
                ->label('Опции / SKU')
                ->addActionLabel('Добавить SKU')
                ->defaultItems(0)
                ->orderColumn('sort_order')
                ->reorderable()
                ->collapsible()
                ->cloneable()
                ->itemLabel(fn (array $state): ?string => $state['sku'] ?? ($state['barcode'] ?? 'SKU'))
                ->schema([
                    Grid::make(4)->schema([
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
                    ]),

                    Repeater::make('langs')
                        ->relationship('langs')
                        ->label('Переводы SKU')
                        ->addActionLabel('Добавить язык')
                        ->collapsible()
                        ->schema([
                            Select::make('lang')->label('Язык')->options(self::locales())->required(),
                            TextInput::make('name')->label('Название')->maxLength(255),
                            Textarea::make('value')->label('Значение')->rows(2),
                            Textarea::make('description')->label('Описание')->rows(2)->columnSpanFull(),
                        ])->columns(2),

                    Select::make('optionValues')
                        ->relationship('optionValues', 'id')
                        ->label('Значения опций SKU')
                        ->multiple()->searchable()->preload()
                        ->getOptionLabelFromRecordUsing(fn (OptionValue $record): string => trim(($record->option?->lang?->name ?? $record->option?->code ?? 'Опция') . ': ' . ($record->lang?->value ?? $record->code ?? "#{$record->id}")))
                        ->columnSpanFull(),

                    Repeater::make('images')
                        ->relationship('images')
                        ->label('Изображения SKU')
                        ->addActionLabel('Добавить изображение')
                        ->orderColumn('position')
                        ->schema(self::imageFields())
                        ->columns(3),
                ])
                ->columnSpanFull(),
        ];
    }

    private static function imagesTab(): array
    {
        return [
            Repeater::make('images')
                ->relationship('images')
                ->label('Изображения товара')
                ->addActionLabel('Добавить изображение')
                ->orderColumn('position')
                ->reorderable()
                ->schema(self::imageFields())
                ->columns(3)
                ->columnSpanFull(),
        ];
    }

    private static function categoriesTab(): array
    {
        return [
            Select::make('categories')
                ->relationship('categories', 'id')
                ->label('Категории')
                ->multiple()
                ->searchable()
                ->preload()
                ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->lang?->name ?? "Категория #{$record->id}")
                ->columnSpanFull(),
        ];
    }

    private static function imageFields(): array
    {
        return [
//            FileUpload::make('name')
//                ->label('Файл')
//                ->image()
//                ->disk('public')
//                ->directory('catalog')
//                ->imageEditor()
//                ->required()
//                ->columnSpan(2),
//            Toggle::make('is_primary')->label('Основное'),
//            TextInput::make('position')->label('Позиция')->numeric()->default(1)->required(),
//            TextInput::make('mime_type')->label('MIME')->maxLength(255),
            ImageUploadFields::schema('catalog/products')
        ];
    }

    private static function locales(): array
    {
        return [
            'ru' => 'Русский',
            'en' => 'English',
            'zh' => '中文',
        ];
    }
}
