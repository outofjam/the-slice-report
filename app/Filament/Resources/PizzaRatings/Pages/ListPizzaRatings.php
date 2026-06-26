<?php

namespace App\Filament\Resources\PizzaRatings\Pages;

use App\Filament\Resources\PizzaRatings\PizzaRatingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPizzaRatings extends ListRecords
{
    protected static string $resource = PizzaRatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
