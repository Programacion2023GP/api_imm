<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\EfectosEconomicosPatrimoniales;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class EfectosEconomicosPatrimonialesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = EfectosEconomicosPatrimoniales::all();

            return ApiResponse::success($permisos, 'lista de efectos economicos patrimoniales');
        } catch (Exception $e) {
            Log::info("efectos economicos patrimoniales index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
