<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/transaction/init', \App\Http\Controllers\Api\Transaction\InitTransactionApiController::class);
Route::post('/transaction/notify', \App\Http\Controllers\Api\Transaction\NotificationTransactionApiController::class);
Route::get('/transaction/status/{requestId}', \App\Http\Controllers\Api\Transaction\GetStatusTransactionApiController::class);
