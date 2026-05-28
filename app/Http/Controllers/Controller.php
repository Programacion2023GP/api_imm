<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    protected $model;

    public function index(Request $request)
    {
        try {
            $data = $this->model::all();

            // Obtener el nombre de la clase del modelo
            $modelName = class_basename($this->model);

            return ApiResponse::success($data, 'Lista de datos de ' . $modelName);
        } catch (Exception $e) {
            $modelName = class_basename($this->model);
            Log::info($modelName . ' - ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
