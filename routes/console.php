<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Sheet;
use App\Jobs\ImportOrdersFromSheet;
use App\Jobs\FetchDeliveryStatus;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\DispatchSheetImports;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
// Artisan::command('sheets:import-orders', function () {
//     $sheets = Sheet::where('is_active', true)
//                    ->whereNotNull('access_token')      // only connected sheets
//                    ->whereNotNull('spreadsheet_id')
//                    ->whereNotNull('column_mapping')
//                    ->get();

//     if ($sheets->isEmpty()) {
//         $this->info('No active connected sheets found.');
//         return;
//     }

//     foreach ($sheets as $sheet) {
//         ImportOrdersFromSheet::dispatch($sheet);
//         $this->info("Dispatched import for sheet: {$sheet->spreadsheet_id}");
//     }
// })->purpose('Import orders from Google Sheets');

Schedule::job(new DispatchSheetImports)->everyMinute();

Schedule::job(new FetchDeliveryStatus)
    ->everyMinute();