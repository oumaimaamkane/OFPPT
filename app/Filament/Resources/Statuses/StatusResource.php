<?php

namespace App\Filament\Resources\Statuses;

use App\Filament\Resources\Statuses\Pages\CreateStatus;
use App\Filament\Resources\Statuses\Pages\EditStatus;
use App\Filament\Resources\Statuses\Pages\ListStatuses;
use App\Filament\Resources\Statuses\Schemas\StatusForm;
use App\Filament\Resources\Statuses\Tables\StatusesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Status;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
class StatusResource extends Resource
{
    protected static ?string $model = Status::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?string $recordTitleAttribute = 'Status';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()->can('ViewAny:Status');
    }

    public static function canCreate(): bool
    {
        return Filament::auth()->user()->can('Create:Status');
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::auth()->user()->can('Update:Status');
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()->can('Delete:Status');
    }
    public static function form(Schema $schema): Schema
    {
        return StatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StatusesTable::configure($table);
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
            'index' => ListStatuses::route('/'),
            'create' => CreateStatus::route('/create'),
            'edit' => EditStatus::route('/{record}/edit'),
        ];
    }
}
