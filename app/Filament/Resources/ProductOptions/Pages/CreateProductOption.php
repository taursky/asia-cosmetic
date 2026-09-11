<?php

namespace App\Filament\Resources\ProductOptions\Pages;

use App\Filament\Resources\ProductOptions\ProductOptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductOption extends CreateRecord
{
    protected static string $resource = ProductOptionResource::class;
}
