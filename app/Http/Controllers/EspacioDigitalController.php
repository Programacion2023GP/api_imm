<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\EspacioDigital;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class EspacioDigitalController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = EspacioDigital::all();

            return ApiResponse::success($permisos, 'lista de espacios digitales');
        } catch (Exception $e) {
            Log::info("espacios digitales index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
