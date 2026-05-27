<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\EfectosPsicologicos;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class EfectosPsicologicosController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = EfectosPsicologicos::all();

            return ApiResponse::success($permisos, 'lista de efectos psicologicos');
        } catch (Exception $e) {
            Log::info("efectos psicologicos index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
