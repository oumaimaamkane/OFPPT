<?php

namespace App\Filament\Resources\Sheets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use App\Jobs\ImportOrdersFromSheet;
use Filament\Notifications\Notification;
class SheetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('spreadsheet_id')
                    ->label('Spreadsheet ID')
                    ->searchable(),

                TextColumn::make('sheet_name')
                    ->label('Nom du sheet')
                    ->searchable(),

                TextColumn::make('last_synced_at')
                    ->label('Dernière synchronisation')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Actif')
                    ->options([
                        '1' => 'Actif',
                        '0' => 'Inactif',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('sync')
                    ->label('Synchroniser')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function ($record) {
                        ImportOrdersFromSheet::dispatchSync($record);
                         Notification::make()
                            ->title('Sync Started')
                            ->success()
                            ->send();
                    }),
                    // Action::make('active')
                    // ->label('Activer')
                    // ->icon('heroicon-o-check-circle')
                    // ->action(function ($record) {
                    //     $record->active();
                    // }),
                    // Action::make('inactive')
                    // ->label('Désactiver')
                    // ->icon('heroicon-o-x-circle')
                    // ->action(function ($record) {
                    //     $record->inactive();
                    // }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
