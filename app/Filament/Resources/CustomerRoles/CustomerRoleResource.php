<?php

namespace App\Filament\Resources\CustomerRoles;

use App\Filament\Resources\CustomerRoles\Pages\CreateCustomerRole;
use App\Filament\Resources\CustomerRoles\Pages\EditCustomerRole;
use App\Filament\Resources\CustomerRoles\Pages\ListCustomerRoles;
use App\Filament\Resources\CustomerRoles\Schemas\CustomerRoleForm;
use App\Filament\Resources\CustomerRoles\Tables\CustomerRolesTable;
use App\Models\CustomerRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerRoleResource extends Resource
{
    protected static ?string $model = CustomerRole::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static string|\UnitEnum|null $navigationGroup = 'ПОКУПАТЕЛИ';
    protected static ?string $navigationLabel = 'Роли покупателей';
    protected static ?string $modelLabel = 'Роль покупателя';
    protected static ?string $pluralModelLabel = 'Роли покупателей';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema { return CustomerRoleForm::configure($schema); }
    public static function table(Table $table): Table { return CustomerRolesTable::configure($table); }
    public static function getPages(): array
    {
        return [
            'index' => ListCustomerRoles::route('/'),
            'create' => CreateCustomerRole::route('/create'),
            'edit' => EditCustomerRole::route('/{record}/edit'),
        ];
    }
}
