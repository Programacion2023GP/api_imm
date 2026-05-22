<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\EspacioParticular;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class EspacioParticularController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = EspacioParticular::all();

            return ApiResponse::success($permisos, 'lista de espacios particulares');
        } catch (Exception $e) {
            Log::info("espacios particulares index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
