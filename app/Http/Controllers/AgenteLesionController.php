<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\AgenteLesion;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AgenteLesionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = AgenteLesion::all();

            return ApiResponse::success($permisos, 'lista de agentes de lesion');
        } catch (Exception $e) {
            Log::info("agentes de lesion index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
