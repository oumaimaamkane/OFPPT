<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Facades\Filament;
class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
            ->visible(fn() => Filament::auth()->user()->can('Delete:Order')),
            ForceDeleteAction::make()
            ->visible(fn() => Filament::auth()->user()->can('Delete:Order')),
            RestoreAction::make()
            ->visible(fn() => Filament::auth()->user()->can('Delete:Order')),
        ];
    }
}
