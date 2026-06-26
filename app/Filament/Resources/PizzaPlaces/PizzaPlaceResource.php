<?php

namespace App\Filament\Resources\PizzaPlaces;

use App\Filament\Resources\PizzaPlaces\Pages\CreatePizzaPlace;
use App\Filament\Resources\PizzaPlaces\Pages\EditPizzaPlace;
use App\Filament\Resources\PizzaPlaces\Pages\ListPizzaPlaces;
use App\Filament\Resources\PizzaPlaces\Schemas\PizzaPlaceForm;
use App\Filament\Resources\PizzaPlaces\Tables\PizzaPlacesTable;
use App\Models\PizzaPlace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PizzaPlaceResource extends Resource
{
    protected static ?string $model = PizzaPlace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static UnitEnum|string|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Places';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PizzaPlaceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PizzaPlacesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPizzaPlaces::route('/'),
            'create' => CreatePizzaPlace::route('/create'),
            'edit' => EditPizzaPlace::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
