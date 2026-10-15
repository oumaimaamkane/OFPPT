<?php

namespace App\Filament\Resources\WarehouseUsers;

use App\Filament\Resources\WarehouseUsers\Pages\CreateWarehouseUser;
use App\Filament\Resources\WarehouseUsers\Pages\EditWarehouseUser;
use App\Filament\Resources\WarehouseUsers\Pages\ListWarehouseUsers;
use App\Filament\Resources\WarehouseUsers\Schemas\WarehouseUserForm;
use App\Filament\Resources\WarehouseUsers\Tables\WarehouseUsersTable;
use App\Models\WarehouseUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;


class WarehouseUserResource extends Resource
{
    protected static ?string $model = WarehouseUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
     public static function canViewAny(): bool
    {
        return Filament::auth()->user()->can('ViewAny:WarehouseUser');
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()->can('Create:WarehouseUser');
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::auth()->user()->can('Update:WarehouseUser');
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()->can('Delete:WarehouseUser');
    }

    

    public static function form(Schema $schema): Schema
    {
        return WarehouseUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseUsersTable::configure($table);
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
            'index' => ListWarehouseUsers::route('/'),
            'create' => CreateWarehouseUser::route('/create'),
            'edit' => EditWarehouseUser::route('/{record}/edit'),
        ];
    }
}
