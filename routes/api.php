<?php

use App\Http\Controllers\PermisosController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('/permisos')->group(function () {
    Route::get('/', [PermisosController::class, 'index']);

    Route::post('/createorUpdate', [PermisosController::class, 'createorUpdate']);

    Route::delete('/delete', [PermisosController::class, 'unChange']);
});
Route::prefix('/usuarios')->group(function () {
    Route::get('/', [UsersController::class, 'index']);

    Route::post('/createorUpdate', [UsersController::class, 'createorUpdate']);

    Route::delete('/delete', [UsersController::class, 'unChange']);
});
Route::prefix('/roles')->group(function () {
    Route::get('/', [RolesController::class, 'index']);
    Route::post('/unchangepermissions', [RolesController::class, 'unChangePermissions']);

    Route::post('/createorUpdate', [RolesController::class, 'createorUpdate']);

    Route::delete('/delete', [RolesController::class, 'unChange']);



    



});