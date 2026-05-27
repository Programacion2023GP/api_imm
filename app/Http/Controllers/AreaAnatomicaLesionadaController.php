<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\AereaAnatomicaLesionada;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AreaAnatomicaLesionadaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = AereaAnatomicaLesionada::all();

            return ApiResponse::success($permisos, 'lista de aerea anatomica lesionada');
        } catch (Exception $e) {
            Log::info("aerea anatomica lesionada index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
