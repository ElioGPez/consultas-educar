<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\ConsultaController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);    
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'register']);
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

Route::get('checkUser', [UserController::class, 'checkUser']);
Route::post('saveUser', [UserController::class, 'saveUser']);
Route::get('getUser', [UserController::class, 'getUser']);

Route::get('all', [ConsultaController::class, 'all']);
Route::get('getNivel', [ConsultaController::class, 'getNivel']);
Route::get('getCargos', [ConsultaController::class, 'getCargos']);
Route::post('savePreference', [ConsultaController::class, 'savePreference']);
Route::get('getVacantes', [ConsultaController::class, 'getVacantes']);

/*
Route::group(['prefix' => 'publicaciones', 'middleware' => 'auth:sanctum'], function () {
    Route::get('all', [ConsultaController::class, 'all']);
    Route::get('getNivel', [ConsultaController::class, 'getNivel']);
});*/