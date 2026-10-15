<?php

namespace App\Filament\Resources\Warehouses;

use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Resources\WArehouses\Tables\WArehousesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $recordTitleAttribute = 'WArehouse';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()->can('ViewAny:Warehouse');
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()->can('Create:Warehouse');
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::auth()->user()->can('Update:Warehouse');
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()->can('Delete:Warehouse');
    }

    public static function form(Schema $schema): Schema
    {
        return WArehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WArehousesTable::configure($table);
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
            'index' => ListWArehouses::route('/'),
            'create' => CreateWArehouse::route('/create'),
            'edit' => EditWArehouse::route('/{record}/edit'),
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
