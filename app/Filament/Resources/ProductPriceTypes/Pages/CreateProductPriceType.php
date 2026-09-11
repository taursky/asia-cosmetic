<?php

namespace App\Filament\Resources\ProductPriceTypes\Pages;

use App\Filament\Resources\ProductPriceTypes\ProductPriceTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductPriceType extends CreateRecord
{
    protected static string $resource = ProductPriceTypeResource::class;
}
