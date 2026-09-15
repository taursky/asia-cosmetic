<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
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
            Section::make('Покупатель')->schema([
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
                    ->dehydrated(fn ($state) => filled($state))
                    ->rule(Password::min(8)),
                Select::make('customerRoles')
                    ->relationship('customerRoles', 'name')
                    ->label('Роли')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
}
