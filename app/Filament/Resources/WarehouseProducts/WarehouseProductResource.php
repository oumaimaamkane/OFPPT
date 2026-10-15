<?php

namespace App\Filament\Resources\WarehouseProducts;

use App\Filament\Resources\WarehouseProducts\Pages\CreateWarehouseProduct;
use App\Filament\Resources\WarehouseProducts\Pages\EditWarehouseProduct;
use App\Filament\Resources\WarehouseProducts\Pages\ListWarehouseProducts;
use App\Filament\Resources\WarehouseProducts\Pages\ViewWarehouseProduct;
use App\Filament\Resources\WarehouseProducts\Schemas\WarehouseProductForm;
use App\Filament\Resources\WarehouseProducts\Tables\WarehouseProductsTable;
use App\Models\WarehouseProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;
class WarehouseProductResource extends Resource
{
    protected static ?string $model = WarehouseProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()->can('ViewAny:WarehouseProduct');
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()->can('Create:WarehouseProduct');
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::auth()->user()->can('Update:WarehouseProduct');
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()->can('Delete:WarehouseProduct');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseProductsTable::configure($table);
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
            'index' => ListWarehouseProducts::route('/'),
            'create' => CreateWarehouseProduct::route('/create'),
            'edit' => EditWarehouseProduct::route('/{record}/edit'),
            // 'view' => ViewWarehouseProduct::route('/{record}'),
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
