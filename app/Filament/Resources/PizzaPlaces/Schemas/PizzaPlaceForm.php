<?php

namespace App\Filament\Resources\PizzaPlaces\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PizzaPlaceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('google_place_id')
                    ->label('Google Place ID')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('address')
                    ->maxLength(500),
                TextInput::make('lat')
                    ->label('Latitude')
                    ->numeric(),
                TextInput::make('lng')
                    ->label('Longitude')
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->length(3)
                    ->dehydrateStateUsing(fn (string $state) => strtoupper($state)),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
