<?php

use Illuminate\Support\Facades\Route;

Route::prefix('voip')->middleware('voip.log')->namespace('App\Http\Controllers\Voip')->group(function () {
  Route::prefix('task-router')->namespace('TaskRouter')->group(function () {
    Route::prefix('workspaces')->group(function () {
      Route::post('callback', 'WorkSpaceController@callback');
    });
  });
});
