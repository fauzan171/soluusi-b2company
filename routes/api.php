<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Midtrans webhook — signature diverifikasi di controller (Fix E.2 #1).
Route::post('midtrans-callback', [\App\Http\Controllers\MidtransController::class, 'callback'])->name('midtrans-callback');
Route::post('midtrans-cancel', [\App\Http\Controllers\MidtransController::class, 'canceledTransaction'])->name('midtrans-cancel');
Route::get('midtrans/schedule-availability', [\App\Http\Controllers\MidtransController::class, 'checkScheduleAvailability'])->name('midtrans.schedule-availability');
