<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Models\Role;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
//                TextInput::make('name')
//                    ->default(null),
//                TextInput::make('email')
//                    ->label('Email address')
//                    ->email()
//                    ->required(),
//                TextInput::make('phone')
//                    ->tel()
//                    ->default(null),
//                TextInput::make('password')
//                    ->password()
//                    ->required(),
//            ]);
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('name')
                            ->label('Имя')
                            ->maxLength(255)
                            ->placeholder('Введите полное имя')
                            ->required(fn($livewire) => $livewire instanceof CreateAdminUser),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->label('Email')
                            ->placeholder('user@example.com')
                            ->prefixIcon('heroicon-o-envelope'),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255)
                            ->label('Телефон')
                            ->placeholder('+7 (999) 123-45-67')
                            ->prefixIcon('heroicon-o-phone'),
                    ])->columns(2),

                Section::make('Безопасность')
                    ->schema([
                        Select::make('roles')
                            ->label('Роли')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required()
                            ->placeholder('Выберите роли для пользователя')
                            ->helperText('Можно выбрать несколько ролей')
                            ->rules(['required', 'array', 'min:1'])
                            ->options(
                                Role::where('is_active', true)
                                    ->pluck('name', 'id')
                            ),

                        TextInput::make('password')
                            ->password()
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn($livewire) => $livewire instanceof CreateAdminUser)
                            ->minLength(8)
                            ->maxLength(255)
                            ->confirmed()
                            ->label('Пароль')
                            ->placeholder('Введите пароль')
                            ->revealable()
                            ->helperText('Пароль должен быть не менее 8 символов'),

                        TextInput::make('password_confirmation')
                            ->password()
                            ->label('Подтверждение пароля')
                            ->placeholder('Повторите пароль')
                            ->revealable()
                            ->dehydrated(false),
                    ])->columns(2),

                Section::make('Дополнительная информация')
                    ->schema([
                        Placeholder::make('created_at')
                            ->label('Создан')
                            ->content(fn($record) => $record?->created_at?->format('d.m.Y H:i') ?? '-'),

                        Placeholder::make('updated_at')
                            ->label('Обновлен')
                            ->content(fn($record) => $record?->updated_at?->format('d.m.Y H:i') ?? '-'),

                        Placeholder::make('deleted_at')
                            ->label('Удален')
                            ->content(fn($record) => $record?->deleted_at?->format('d.m.Y H:i') ?? '-'),
                    ])->columns(3)
                    ->hidden(fn($livewire) => $livewire instanceof CreateAdminUser),
            ]);
    }
}
