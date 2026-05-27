<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\EfectosFisicos;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class EfectosFisicosController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = EfectosFisicos::all();

            return ApiResponse::success($permisos, 'lista de efectos fisicos');
        } catch (Exception $e) {
            Log::info("efectos fisicos index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
