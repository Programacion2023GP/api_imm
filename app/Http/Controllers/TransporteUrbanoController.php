<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\TransporteUrbano;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TransporteUrbanoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = TransporteUrbano::all();

            return ApiResponse::success($permisos, 'lista de transportes urbanos');
        } catch (Exception $e) {
            Log::info("transportes urbanos index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
