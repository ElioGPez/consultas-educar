<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('/login', 'App\Http\Controllers\AuthController@login');
    Route::post('/register', 'App\Http\Controllers\AuthController@register');
    Route::post('/logout', 'App\Http\Controllers\AuthController@logout');
    Route::post('/refresh', 'App\Http\Controllers\AuthController@refresh');
    Route::get('/user-profile', 'App\Http\Controllers\AuthController@userProfile');    
});

Route::post('login', 'App\Http\Controllers\api\UserController@login');
Route::post('register', 'App\Http\Controllers\api\UserController@register');
Route::post('logout', 'App\Http\Controllers\api\UserController@logout')->middleware('auth:sanctum');

Route::get('checkUser', 'App\Http\Controllers\api\UserController@checkUser');
Route::post('saveUser', 'App\Http\Controllers\api\UserController@saveUser');
Route::get('getUser', 'App\Http\Controllers\api\UserController@getUser');
Route::post('saveFcmToken', 'App\Http\Controllers\api\UserController@saveFcmToken');

Route::get('all', 'App\Http\Controllers\api\ConsultaController@all');
Route::get('getNivel', 'App\Http\Controllers\api\ConsultaController@getNivel');
Route::get('getCargos', 'App\Http\Controllers\api\ConsultaController@getCargos');
Route::post('savePreference', 'App\Http\Controllers\api\ConsultaController@savePreference');
Route::get('getVacantes', 'App\Http\Controllers\api\ConsultaController@getVacantes');
Route::get('getVacanteDetalle/{id}', 'App\Http\Controllers\api\ConsultaController@getVacanteDetalle');
Route::get('getPreferences', 'App\Http\Controllers\api\ConsultaController@getPreferences');
Route::delete('deletePreference/{id}', 'App\Http\Controllers\api\ConsultaController@deletePreference');