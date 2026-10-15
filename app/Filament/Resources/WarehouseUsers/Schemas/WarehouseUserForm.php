<?php

namespace App\Filament\Resources\WarehouseUsers\Schemas;

use Filament\Schemas\Schema;
use App\Models\User;
class WarehouseUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('user_id')
                    ->options(User::WarehouseManager()->pluck('name', 'id'))
                    ->label('User')
                    ->required(),
                \Filament\Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label('Warehouse')
                    ->required(),
            ]);
    }
}

