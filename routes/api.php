<?php

use App\Http\Controllers\ActividadPrincipalController;
use App\Http\Controllers\AgenteLesionController;
use App\Http\Controllers\AmbitoViolenciaController;
use App\Http\Controllers\AreaAnatomicaLesionadaController;
use App\Http\Controllers\ConsecuenciasSexualesController;
use App\Http\Controllers\DiscapacidadesController;
use App\Http\Controllers\EfectosEconomicosPatrimonialesController;
use App\Http\Controllers\EfectosFisicosController;
use App\Http\Controllers\EfectosPsicologicosController;
use App\Http\Controllers\EspacioDigitalController;
use App\Http\Controllers\EspacioParticularController;
use App\Http\Controllers\EspacioPublicoController;
use App\Http\Controllers\EstadoCivilController;
use App\Http\Controllers\IdentidadGeneroController;
use App\Http\Controllers\IngresosPromediosController;
use App\Http\Controllers\OrientacionSexualController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\RelacionController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\ServicioMedicoController;
use App\Http\Controllers\TipoViolenciaController;
use App\Http\Controllers\TransporteForaneoController;
use App\Http\Controllers\TransportePrivadoController;
use App\Http\Controllers\TransporteUrbanoController;
use App\Http\Controllers\UltimoGradoEstudiosController;
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
    Route::prefix('/tipoviolencia')->group(function () {
        Route::get('/', [TipoViolenciaController::class, 'index']);
    });
    Route::prefix('/ambitoviolencia')->group(function () {
        Route::get('/', [AmbitoViolenciaController::class, 'index']);
    });


    Route::prefix('/efectosfisicos')->group(function () {
        Route::get('/', [EfectosFisicosController::class, 'index']);
    });
    Route::prefix('/consecuenciassexuales')->group(function () {
        Route::get('/', [ConsecuenciasSexualesController::class, 'index']);
    });
    Route::prefix('/efectospsicologicos')->group(function () {
        Route::get('/', [EfectosPsicologicosController::class, 'index']);
    });
    Route::prefix('/efectoseconmicospatrimoniales')->group(function () {
        Route::get('/', [EfectosEconomicosPatrimonialesController::class, 'index']);
    });
    Route::prefix('/agentelesion')->group(function () {
        Route::get('/', [AgenteLesionController::class, 'index']);
    });
    Route::prefix('/areaanatomicalesionada')->group(function () {
        Route::get('/', [AreaAnatomicaLesionadaController::class, 'index']);
    });


    Route::prefix('/orientacionsexual')->group(function () {
        Route::get('/', [OrientacionSexualController::class, 'index']);
    });
    Route::prefix('/identidadgenero')->group(function () {
        Route::get('/', [IdentidadGeneroController::class, 'index']);
    });
    Route::prefix('/estadocivil')->group(function () {
        Route::get('/', [EstadoCivilController::class, 'index']);
    });
    Route::prefix('/gradoestudios')->group(function () {
        Route::get('/', [UltimoGradoEstudiosController::class, 'index']);
    });
    Route::prefix('/ingresospromedios')->group(function () {
        Route::get('/', [IngresosPromediosController::class, 'index']);
    });
    Route::prefix('/actividadprincipal')->group(function () {
        Route::get('/', [ActividadPrincipalController::class, 'index']);
    });
    Route::prefix('/serviciomedico')->group(function () {
        Route::get('/', [ServicioMedicoController::class, 'index']);
    });
    Route::prefix('/discapacidades')->group(function () {
        Route::get('/', [DiscapacidadesController::class, 'index']);
    });
    Route::prefix('/relacion')->group(function () {
        Route::get('/', [RelacionController::class, 'index']);
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
