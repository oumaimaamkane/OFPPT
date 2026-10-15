<?php

namespace App\Filament\Resources\DeliveryCompanies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DeliveryCompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('token')
                    ->label('Token/API Key')
                    ->required(),
                TextInput::make('api_id')
                    ->label('API ID')
                    ,
            ]);
    }
}
