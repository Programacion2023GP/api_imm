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
    public function createOrUpdate(Request $request)
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:255',
            ]);

            $modelName = class_basename($this->model);

            $record = $this->model::updateOrCreate(
                ['id' => $request->id],
                ['nombre' => $request->nombre]
            );

            $message = $request->id
                ? "Actualizado correctamente"
                : "Creado correctamente";

            return ApiResponse::success($record, $message);
        } catch (Exception $e) {
            $modelName = class_basename($this->model);
            Log::info($modelName . ' - ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
