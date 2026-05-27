<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\TransportePrivado;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;





class TransportePrivadoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = TransportePrivado::all();

            return ApiResponse::success($permisos, 'lista de transportes privados');
        } catch (Exception $e) {
            Log::info("transportes index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
