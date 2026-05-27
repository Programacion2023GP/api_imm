<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\ConsecuenciasSexuales;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class ConsecuenciasSexualesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = ConsecuenciasSexuales::all();

            return ApiResponse::success($permisos, 'lista de consecuencias sexuales');
        } catch (Exception $e) {
            Log::info("consecuencias sexuales index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
