<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->namespace('App\Http\Controllers\Api\V1')->group(function () {
    Route::prefix('workers')->group(function () {
        Route::post('login', 'WorkersController@login');
    });
});
