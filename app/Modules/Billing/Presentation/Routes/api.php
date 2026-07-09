<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Billing\Presentation\Controllers\InvoiceController;

Route::prefix('api/v1/billing')
    ->middleware(['api', 'module.active:Billing', 'auth:sanctum'])
    ->group(function () {
        
        Route::get('/invoices', [InvoiceController::class, 'index']);
        
    });
