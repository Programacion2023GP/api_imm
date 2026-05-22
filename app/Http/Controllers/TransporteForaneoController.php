<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use App\Models\TransporteForaneo;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;



class TransporteForaneoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $permisos = TransporteForaneo::all();

            return ApiResponse::success($permisos, 'lista de transportes foraneos');
        } catch (Exception $e) {
            Log::info("transportes foraneos index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
