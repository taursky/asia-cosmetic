<?php
namespace App\Filament\Resources\CustomerRoles\Schemas;
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
            Section::make('Роль')->schema([
                TextInput::make('name')->label('Название')->required()->maxLength(255),
                TextInput::make('code')->label('Код')->required()->maxLength(255)->unique(ignoreRecord: true),
                Textarea::make('description')->label('Описание')->columnSpanFull(),
                TextInput::make('sort_order')->label('Сортировка')->numeric()->default(0)->required(),
                Toggle::make('is_active')->label('Активна')->default(true),
            ])
                ->columnSpanFull()
                ->columns(2),
        ]);
    }
}
