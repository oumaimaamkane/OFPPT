<?php

namespace App\Jobs;
use App\Models\Sheet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Jobs\ImportOrdersFromSheet;
class DispatchSheetImports implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Sheet::where('is_active', true)
            ->whereNotNull('spreadsheet_id')
            ->each(fn(Sheet $sheet) => ImportOrdersFromSheet::dispatch($sheet));
    }
}
