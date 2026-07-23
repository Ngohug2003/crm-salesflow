<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user())
        ->middleware(['auth:sanctum', 'request.context.authenticated']);
});
