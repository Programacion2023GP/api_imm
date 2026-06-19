<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CatalogosController extends Controller
{
    public function index($tabla){
      try {
            if (Schema::hasTable($tabla)) {
                $data = DB::table($tabla)->get();
                return ApiResponse::success($data, 'Lista de datos de ' . $tabla);
            }
      } catch (\Exception $e) {
            Log::info($tabla . ' - ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
      }
    }
}
