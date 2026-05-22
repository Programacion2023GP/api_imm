<?php

use App\Http\Controllers\EspacioDigitalController;
use App\Http\Controllers\EspacioParticularController;
use App\Http\Controllers\EspacioPublicoController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\TransporteForaneoController;
use App\Http\Controllers\TransportePrivadoController;
use App\Http\Controllers\TransporteUrbanoController;
use App\Http\Controllers\UsersController;
use App\Models\EspacioDigital;
use App\Models\EspacioParticular;
use App\Models\TransportePrivado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->group(
    function () {

        Route::post('/login', [UsersController::class, 'login']);
    }
);
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('/auth')->group(
        function () {

            Route::get('/logout', [UsersController::class, 'logout']);
        }
    );
    Route::prefix('/permisos')->group(function () {
        Route::get('/', [PermisosController::class, 'index']);

        Route::post('/createorUpdate', [PermisosController::class, 'createorUpdate']);

        Route::delete('/delete', [PermisosController::class, 'unChange']);
    });
    Route::prefix('/espaciodigital')->group(function () {
        Route::get('/', [EspacioDigitalController::class, 'index']);
    });
    Route::prefix('/espacioparticular')->group(function () {
        Route::get('/', [EspacioParticularController::class, 'index']);
    });
    Route::prefix('/espaciopublico')->group(function () {
        Route::get('/', [EspacioPublicoController::class, 'index']);
    });
    Route::prefix('/transporteforaneo')->group(function () {
        Route::get('/', [TransporteForaneoController::class, 'index']);
    });
    Route::prefix('/transporteurbano')->group(function () {
        Route::get('/', [TransporteUrbanoController::class, 'index']);
    });
    Route::prefix('/transporteprivado')->group(function () {
        Route::get('/', [TransportePrivadoController::class, 'index']);
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
});
