<?php

namespace App\Filament\Resources\CustomerRoles\Schemas;

use App\Models\ProductPriceType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerRoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Роль')
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('code')
                        ->label('Код')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Textarea::make('description')
                        ->label('Описание')
                        ->rows(3)
                        ->columnSpanFull(),

                    TextInput::make('level')
                        ->label('Уровень роли')
                        ->helperText('Чем выше значение, тем выше коммерческий уровень покупателя.')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->minValue(0),

                    TextInput::make('sort_order')
                        ->label('Сортировка')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),

                    Toggle::make('is_default')
                        ->label('Роль по умолчанию')
                        ->helperText('Назначается новым покупателям и используется как базовая роль.')
                        ->default(false),

                    Toggle::make('is_auto')
                        ->label('Участвует в автоматическом пересчёте')
                        ->helperText('Разрешает автоматическое повышение/понижение до этой роли.')
                        ->default(true),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Цены и квалификация')
                ->description('Настройки коммерческого уровня, порогов заказа и срока действия роли.')
                ->schema([
                    Select::make('product_price_type_id')
                        ->label('Тип цены')
                        ->options(fn (): array => ProductPriceType::query()
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('order_threshold_amount')
                        ->label('Порог суммы заказа')
                        ->helperText('Если текущая корзина достигает этой суммы, для заказа может применяться цена этой роли.')
                        ->numeric()
                        ->prefix('₽')
                        ->step(0.01)
                        ->minValue(0)
                        ->nullable(),

                    TextInput::make('qualification_amount')
                        ->label('Сумма покупок для роли')
                        ->helperText('Минимальный оплаченный оборот за период, необходимый для сохранения/получения роли.')
                        ->numeric()
                        ->prefix('₽')
                        ->step(0.01)
                        ->default(0)
                        ->required()
                        ->minValue(0),

                    TextInput::make('qualification_period_days')
                        ->label('Период квалификации, дней')
                        ->helperText('За сколько последних дней считать оплаченные заказы.')
                        ->numeric()
                        ->default(180)
                        ->required()
                        ->minValue(1),

                    TextInput::make('validity_days')
                        ->label('Срок действия роли, дней')
                        ->helperText('На сколько дней продлевается роль после подтверждения условий.')
                        ->numeric()
                        ->default(180)
                        ->required()
                        ->minValue(1),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
