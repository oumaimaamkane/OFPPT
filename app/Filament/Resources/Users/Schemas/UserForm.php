<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Models\Warehouse;
use App\Models\User;
use Filament\Forms\Components\Utilities\Get;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $warehouseManagerRoleId = SpatieRole::where('name', 'Warehouse manager')->value('id');

        return $schema
            ->components([
                TextInput::make('name')
                ->label('Nom')
                    ->required(),
                TextInput::make('email')
                ->label('email')
                ->email()
                ->disabledOn('edit')
                  ->required(),
                TextInput::make('password')
                ->label('password')
                ->required()
                ->password()
                ->visibleOn('create')
                ->dehydrated(fn ($state) => filled($state)),
                Select::make('roles')
                ->relationship('roles', 'name')
                ->label('roles')
                    ->options(function () {
                        return  SpatieRole::all()->pluck('name', 'id')->toArray();
                    })
                    ->live()

                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('warehouse_id', null);
                    })
                ,
                Select::make('warehouse_id')
            ->relationship('warehouses', 'name')
            ->label('Warehouse')
            ->required(fn ($get) => $get('roles') == $warehouseManagerRoleId)
            ->visible(fn ($get) => $get('roles') == $warehouseManagerRoleId)
            ->options(fn () => Warehouse::pluck('name', 'id')->toArray())
            ->live(),

            ]);
    }
}
