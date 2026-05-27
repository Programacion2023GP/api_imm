<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\AmbitoViolencia;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AmbitoViolenciaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = AmbitoViolencia::all();

            return ApiResponse::success($permisos, 'lista de ambito violencia');
        } catch (Exception $e) {
            Log::info("ambito violencia index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
