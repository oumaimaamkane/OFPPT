<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Spatie\Permission\Models\Role as SpatieRole;
use Filament\Tables\Filters\SelectFilter;
use STS\FilamentImpersonate\Actions\Impersonate;
class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                ->label('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                ->label('roles')
                    ->badge()
                    ->separator(','),
               
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('roles')
                ->options(function () {
                    return  SpatieRole::all()->pluck('name', 'id')->toArray();
                })
            ])
            ->recordActions([
                EditAction::make(),
                Impersonate::make(),
                Action::make('assign_roles')
                ->label('Assign roles')
                ->icon('heroicon-o-user-group')
                ->color('primary')
                ->schema([
                            Select::make('roles')
                                ->options(function () {
                                  return  SpatieRole::all()->pluck('name', 'id')->toArray();
                                })
                ])
                ->action(function ($record, $data) {
                    $record->roles()->sync($data['roles']);
                }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
