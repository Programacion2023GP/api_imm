<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\TipoViolencia;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TipoViolenciaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = TipoViolencia::all();

            return ApiResponse::success($permisos, 'lista de tipos violencia');
        } catch (Exception $e) {
            Log::info("tipos violencia index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
