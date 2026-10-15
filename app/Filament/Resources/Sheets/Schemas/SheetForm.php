<?php

namespace App\Filament\Resources\Sheets\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;

class SheetForm
{
    public static function configure(Schema $schema): Schema
    {
       return $schema
    ->components([
        TextInput::make('spreadsheet_id')
            ->label('Spreadsheet ID')
            ->required()
            ->placeholder('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms')
            ->helperText('Found in your Google Sheets URL: https://docs.google.com/spreadsheets/d/spreadsheet_id/edit , make sure the sheet is shared with the service account email: suivilead@warehousemanagi.iam.gserviceaccount.com'),

        TextInput::make('sheet_name')
            ->label('Nom de la feuille')
            ->required()
            ->default('Sheet1')
            ->placeholder('Sheet1'),

        TextInput::make('brand_name')
            ->label('Marque')
            ->required()
            ->default('Sheet1')
            ->placeholder('Sheet1'),

        TextInput::make('header_row')
            ->label('Ligne d\'en-tête')
            ->numeric()
            ->required()
            ->default(1)
            ->minValue(1),

        KeyValue::make('column_mapping')
            ->label('Mapping des colonnes')
            ->keyLabel('Colonne de la feuille (ex: A, B, C)')
            ->valueLabel('Champ de la commande'),

      

        Toggle::make('is_active')
            ->label('Active')
            ->default(true),
    ]);
    }
}
