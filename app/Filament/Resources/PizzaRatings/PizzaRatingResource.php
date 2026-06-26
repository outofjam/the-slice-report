<?php

namespace App\Filament\Resources\PizzaRatings;

use App\Filament\Resources\PizzaRatings\Pages\CreatePizzaRating;
use App\Filament\Resources\PizzaRatings\Pages\EditPizzaRating;
use App\Filament\Resources\PizzaRatings\Pages\ListPizzaRatings;
use App\Filament\Resources\PizzaRatings\Schemas\PizzaRatingForm;
use App\Filament\Resources\PizzaRatings\Tables\PizzaRatingsTable;
use App\Models\PizzaRating;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PizzaRatingResource extends Resource
{
    protected static ?string $model = PizzaRating::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static UnitEnum|string|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Ratings';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PizzaRatingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PizzaRatingsTable::configure($table);
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
            'index' => ListPizzaRatings::route('/'),
            'create' => CreatePizzaRating::route('/create'),
            'edit' => EditPizzaRating::route('/{record}/edit'),
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
