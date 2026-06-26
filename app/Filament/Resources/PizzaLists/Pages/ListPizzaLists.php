<?php

namespace App\Filament\Resources\PizzaLists\Pages;

use App\Filament\Resources\PizzaLists\PizzaListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPizzaLists extends ListRecords
{
    protected static string $resource = PizzaListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
