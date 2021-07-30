<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\vkController;

Route::prefix('vk')->group(function () {
    Route::resource('/list_community', vkController::class);
    Route::get('/get_user_data', 'App\Http\Controllers\GetUserDataController@index');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
