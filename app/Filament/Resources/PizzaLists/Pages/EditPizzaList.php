<?php

namespace App\Filament\Resources\PizzaLists\Pages;

use App\Filament\Resources\PizzaLists\PizzaListResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPizzaList extends EditRecord
{
    protected static string $resource = PizzaListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
