<?php

use App\Http\Controllers\Webhooks\TelegramController;
use App\Http\Controllers\Webhooks\AmountController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhook', TelegramController::class);
Route::post('/amount', AmountController::class)
    ->middleware(\App\Http\Middleware\VerifyApiKeyMiddleware::class);
