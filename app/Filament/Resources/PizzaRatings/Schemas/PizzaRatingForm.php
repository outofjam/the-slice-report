<?php

namespace App\Filament\Resources\PizzaRatings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PizzaRatingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Select::make('pizza_place_id')
                    ->label('Place')
                    ->relationship('pizzaPlace', 'name')
                    ->searchable()
                    ->required(),
                Select::make('list_id')
                    ->label('List')
                    ->relationship('list', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0),
                TextInput::make('currency')
                    ->required()
                    ->length(3)
                    ->disabled(),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10)
                    ->step(0.1),
                TextInput::make('note')
                    ->maxLength(1000),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
