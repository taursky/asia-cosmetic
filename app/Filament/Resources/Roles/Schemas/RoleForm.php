<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Filament\Resources\Roles\Pages\CreateRole;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->label('Название роли')
                            ->placeholder('Введите название роли')
                            ->helperText('Уникальное название роли (например: "Администратор")'),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->label('Slug')
                            ->placeholder('администратор')
                            ->helperText('Уникальный идентификатор (только латиница, цифры и дефис)')
                            ->rules(['regex:/^[a-z0-9-]+$/'])
                            ->default(fn ($livewire) => $livewire instanceof CreateRole ? '' : null),

                        Textarea::make('description')
                            ->maxLength(65535)
                            ->label('Описание')
                            ->rows(3)
                            ->placeholder('Краткое описание роли')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->label('Активна')
                            ->helperText('Активные роли могут быть назначены пользователям'),
                    ])->columns(2),

                Section::make('Статистика')
                    ->schema([
                        Placeholder::make('users_count')
                            ->label('Количество пользователей')
                            ->content(fn ($record) => $record?->adminUsers()->count() ?? 0),
                    ])
                    ->hidden(fn ($livewire) => $livewire instanceof CreateRole),
            ]);
    }
}
