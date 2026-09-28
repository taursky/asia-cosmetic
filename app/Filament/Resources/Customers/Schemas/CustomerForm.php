<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\CustomerRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Покупатель')
                ->schema([
                    TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(32)
                        ->unique(ignoreRecord: true),
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                    TextInput::make('password')
                        ->label('Новый пароль')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->rule(Password::min(8)),
                ])
                ->columnSpanFull()
                ->columns(2),

            Section::make('Коммерческие условия')
                ->description('Текущая коммерческая роль определяет тип цены покупателя.')
                ->schema([
                    Select::make('customer_role_id')
                        ->label('Уровень / роль покупателя')
                        ->options(fn (): array => CustomerRole::query()
                            ->with('priceType')
                            ->where('is_active', true)
                            ->orderBy('level')
                            ->get()
                            ->mapWithKeys(fn (CustomerRole $role): array => [
                                $role->id => $role->name
                                    . ($role->priceType ? ' — ' . $role->priceType->name : ' — без типа цены'),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Toggle::make('customer_role_locked')
                        ->label('Не менять роль автоматически')
                        ->helperText('Если включено, авто-повышение и авто-понижение роли отключены.'),
                    DateTimePicker::make('customer_role_valid_until')
                        ->label('Роль действует до')
                        ->seconds(false),
                ])
                ->columnSpanFull()
                ->columns(3),

            Section::make('Реквизиты покупателя')
                ->relationship('customerProfile')
                ->schema([
                    Select::make('legal_type')
                        ->label('Правовой статус')
                        ->options([
                            'individual' => 'Физическое лицо',
                            'individual_entrepreneur' => 'Индивидуальный предприниматель',
                            'legal_entity' => 'Юридическое лицо',
                        ])
                        ->required()
                        ->default('individual'),
                    Select::make('verification_status')
                        ->label('Статус проверки')
                        ->options([
                            'draft' => 'Черновик',
                            'pending' => 'На проверке',
                            'verified' => 'Подтверждены',
                            'rejected' => 'Требуют исправления',
                        ])
                        ->required()
                        ->default('draft'),
                    DateTimePicker::make('verified_at')
                        ->label('Подтверждено')
                        ->seconds(false),
                    Textarea::make('verification_comment')
                        ->label('Комментарий менеджера')
                        ->rows(2)
                        ->columnSpanFull(),

                    TextInput::make('company_name')->label('Краткое наименование')->maxLength(255),
                    TextInput::make('full_company_name')->label('Полное наименование')->maxLength(255),
                    TextInput::make('inn')->label('ИНН')->maxLength(12),
                    TextInput::make('kpp')->label('КПП')->maxLength(9),
                    TextInput::make('ogrn')->label('ОГРН')->maxLength(15),
                    TextInput::make('ogrnip')->label('ОГРНИП')->maxLength(15),
                    Textarea::make('legal_address')->label('Юридический адрес')->rows(2),
                    Textarea::make('actual_address')->label('Фактический адрес')->rows(2),

                    TextInput::make('bank_name')->label('Банк')->maxLength(255)->columnSpanFull(),
                    TextInput::make('bank_bik')->label('БИК')->maxLength(9),
                    TextInput::make('bank_account')->label('Расчётный счёт')->maxLength(20),
                    TextInput::make('bank_corr_account')->label('Корреспондентский счёт')->maxLength(20),

                    TextInput::make('director_name')->label('Руководитель / подписант')->maxLength(255),
                    TextInput::make('director_position')->label('Должность')->maxLength(255),
                    TextInput::make('contact_name')->label('Контактное лицо')->maxLength(255),
                    TextInput::make('contact_phone')->label('Контактный телефон')->tel()->maxLength(255),
                    TextInput::make('contact_email')->label('Контактный email')->email()->maxLength(255),
                ])
                ->columnSpanFull()
                ->columns(3),

            Section::make('Документы')
                ->description('Договоры, счета и другие документы, доступные покупателю в личном кабинете.')
                ->schema([
                    Repeater::make('customerDocuments')
                        ->relationship('customerDocuments')
                        ->label('Документы')
                        ->addActionLabel('Добавить документ')
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->schema([
                            Select::make('type')
                                ->label('Тип')
                                ->options([
                                    'contract' => 'Договор',
                                    'invoice' => 'Счёт',
                                    'reconciliation' => 'Акт сверки',
                                    'other' => 'Другое',
                                ])
                                ->required(),
                            TextInput::make('title')
                                ->label('Название')
                                ->required()
                                ->maxLength(255),
                            Hidden::make('disk')->default('local'),
                            FileUpload::make('path')
                                ->label('Файл')
                                ->disk('local')
                                ->directory('customer-documents')
                                ->visibility('private')
                                ->downloadable()
                                ->required(),
                            TextInput::make('external_id')
                                ->label('ID во внешней системе')
                                ->maxLength(255),
                            DateTimePicker::make('available_from')
                                ->label('Доступен с')
                                ->seconds(false),
                            DateTimePicker::make('expires_at')
                                ->label('Доступен до')
                                ->seconds(false),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
