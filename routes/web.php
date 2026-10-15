<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\ProductController;
Route::get('/', function () {
    return redirect()->route('filament.app.pages.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/products/{product}/print-ticket', [ProductController::class, 'printTicket'])->name('products.print_ticket');
});
Route::get('/google/connect',  [GoogleOAuthController::class, 'redirect'])->name('google.connect');
Route::get('/google/callback', [GoogleOAuthController::class, 'callback'])->name('google.callback');
