<?php

namespace App\Filament\Resources\PizzaPlaces\Pages;

use App\Filament\Resources\PizzaPlaces\PizzaPlaceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPizzaPlaces extends ListRecords
{
    protected static string $resource = PizzaPlaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
