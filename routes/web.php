<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return view('welcome');
});


// Route for testing the invoice
Route::get('/test-invoice', [InvoiceController::class, 'showTestInvoice']);
Route::get('/test-invoice-pro', [InvoiceController::class, 'showProfessionalInvoice']);
Route::get('/test-invoice-adv', [InvoiceController::class, 'showAdvancedInvoice']);
