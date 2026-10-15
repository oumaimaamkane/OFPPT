<?php

namespace App\Filament\Resources\Sheets;

use App\Filament\Resources\Sheets\Pages\CreateSheet;
use App\Filament\Resources\Sheets\Pages\EditSheet;
use App\Filament\Resources\Sheets\Pages\ListSheets;
use App\Filament\Resources\Sheets\Schemas\SheetForm;
use App\Filament\Resources\Sheets\Tables\SheetsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Sheet;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
class SheetResource extends Resource
{
    protected static ?string $model = Sheet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $recordTitleAttribute = 'Sheet';
    public static function canViewAny(): bool
    {
        return Filament::auth()->user()->can('ViewAny:Sheet');
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()->can('Create:Sheet');
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::auth()->user()->can('Update:Sheet');
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()->can('Delete:Sheet');
    }

    public static function form(Schema $schema): Schema
    {
        return SheetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SheetsTable::configure($table);
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
            'index' => ListSheets::route('/'),
            'create' => CreateSheet::route('/create'),
            'edit' => EditSheet::route('/{record}/edit'),
        ];
    }
}
