<?php

namespace App\Filament\Resources\CustomerRoles\Pages;

use App\Filament\Resources\CustomerRoles\CustomerRoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerRole extends CreateRecord
{
    protected static string $resource = CustomerRoleResource::class;
}
