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

  Route::prefix('voice')->namespace('Voice')->group(function () {
    Route::prefix('call')->group(function () {
      Route::post('answer', 'CallingController@answer');
      Route::post('status/{call_sid?}', 'CallingController@status');
      Route::post('fallback/{call_sid?}', 'CallingController@fallback');
    });
  });
});
