<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\StoreController;

Route::post('/register', [AuthController::class,'register']);
Route::post('/login',    [AuthController::class,'login']);

Route::get('/ebooks',      [EbookController::class,'index']);
Route::get('/ebooks/{id}', [EbookController::class,'show']);

Route::middleware('auth:sanctum')->group(function() {
    Route::post('/purchase/{id}', [StoreController::class,'purchase']);
    Route::post('/ebooks/{id}/issue-token', [EbookController::class,'issueToken']);
    Route::get('/ebooks/{id}/download',     [EbookController::class,'download']);
    Route::get('/ebooks/{id}/key',          [EbookController::class,'issueKey']);
});

