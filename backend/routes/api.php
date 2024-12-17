<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

// Paypal pay :
Route::post('/paypal/create', [PaymentController::class, 'createPayment']);
Route::post('/paypal/execute', [PaymentController::class, 'executePayment']);
