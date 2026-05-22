<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\EspacioPublico;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class EspacioPublicoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = EspacioPublico::all();

            return ApiResponse::success($permisos, 'lista de espacio publico');
        } catch (Exception $e) {
            Log::info("espacio publico index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
