<?php

namespace App\Filament\Resources\PizzaPlaces\Pages;

use App\Filament\Resources\PizzaPlaces\PizzaPlaceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPizzaPlace extends EditRecord
{
    protected static string $resource = PizzaPlaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
