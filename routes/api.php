<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/short', [App\Http\Controllers\ShortUrlController::class, 'getCodeDetails']);
Route::get('/redirects', [App\Http\Controllers\ShortUrlController::class, 'getRedirects']);
