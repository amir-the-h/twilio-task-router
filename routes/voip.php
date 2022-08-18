<?php

use Illuminate\Support\Facades\Route;

Route::prefix('voip')->middleware('voip.log')->namespace('App\Http\Controllers\Voip')->group(function () {
  Route::prefix('task-router')->namespace('TaskRouter')->group(function () {
    Route::prefix('workspaces/{workspace_sid}')->group(function () {
      Route::post('callback', 'WorkspaceController@callback');

      Route::prefix('workflows/{workflow_sid}')->group(function () {
        Route::post('assignment', 'WorkflowController@assignment');
        Route::post('fallback', 'WorkflowController@fallback');
      });
    });
  });
});
