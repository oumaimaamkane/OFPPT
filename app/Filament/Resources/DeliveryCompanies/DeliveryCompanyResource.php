<?php

namespace App\Filament\Resources\DeliveryCompanies;

use App\Filament\Resources\DeliveryCompanies\Pages\CreateDeliveryCompany;
use App\Filament\Resources\DeliveryCompanies\Pages\EditDeliveryCompany;
use App\Filament\Resources\DeliveryCompanies\Pages\ListDeliveryCompanies;
use App\Filament\Resources\DeliveryCompanies\Schemas\DeliveryCompanyForm;
use App\Filament\Resources\DeliveryCompanies\Tables\DeliveryCompaniesTable;
use BackedEnum;
use App\Models\DeliveryCompany;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class DeliveryCompanyResource extends Resource
{
    protected static ?string $model = DeliveryCompany::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $recordTitleAttribute = 'Delivery company';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()->can('ViewAny:DeliveryCompany');
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()->can('Create:DeliveryCompany');
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::auth()->user()->can('Update:DeliveryCompany');
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()->can('Delete:DeliveryCompany');
    }

    public static function form(Schema $schema): Schema
    {
        return DeliveryCompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryCompaniesTable::configure($table);
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
            'index' => ListDeliveryCompanies::route('/'),
            'create' => CreateDeliveryCompany::route('/create'),
            'edit' => EditDeliveryCompany::route('/{record}/edit'),
        ];
    }
}
