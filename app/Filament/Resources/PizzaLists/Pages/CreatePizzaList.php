<?php

namespace App\Filament\Resources\PizzaLists\Pages;

use App\Filament\Resources\PizzaLists\PizzaListResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePizzaList extends CreateRecord
{
    protected static string $resource = PizzaListResource::class;
}
