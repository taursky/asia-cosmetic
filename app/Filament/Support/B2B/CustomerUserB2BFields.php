<?php

namespace App\Filament\Support\B2B;

use App\Models\CustomerRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class CustomerUserB2BFields
{
    public static function schema(): array
    {
        return [
            Section::make('B2B уровень покупателя')
                ->schema([
                    Select::make('customer_role_id')
                        ->label('Текущая роль')
                        ->options(fn () => CustomerRole::query()->orderBy('level')->pluck('name', 'id'))
                        ->searchable()->preload(),
                    Toggle::make('customer_role_locked')
                        ->label('Не менять роль автоматически')
                        ->helperText('Если включено, оборот и сроки не повышают и не понижают роль.'),
                ])->columns(2),
        ];
    }
}
