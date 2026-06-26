<?php

namespace App\Filament\Resources\PizzaLists;

use App\Filament\Resources\PizzaLists\Pages\CreatePizzaList;
use App\Filament\Resources\PizzaLists\Pages\EditPizzaList;
use App\Filament\Resources\PizzaLists\Pages\ListPizzaLists;
use App\Filament\Resources\PizzaLists\Schemas\PizzaListForm;
use App\Filament\Resources\PizzaLists\Tables\PizzaListsTable;
use App\Models\PizzaList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PizzaListResource extends Resource
{
    protected static ?string $model = PizzaList::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static UnitEnum|string|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Lists';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PizzaListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PizzaListsTable::configure($table);
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
            'index' => ListPizzaLists::route('/'),
            'create' => CreatePizzaList::route('/create'),
            'edit' => EditPizzaList::route('/{record}/edit'),
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
