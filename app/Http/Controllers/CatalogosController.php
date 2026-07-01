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
    public function store(Request $request, $tabla)
    {
        try {
            // 1. Validar que la tabla existe
            if (!Schema::hasTable($tabla)) {
                return ApiResponse::error('Tabla no encontrada', 404);
            }

            // 2. Obtener las columnas de la tabla
            $columns = Schema::getColumnListing($tabla);

            // 3. Filtrar solo los campos que existen en la tabla
            $data = $request->only($columns);

            // 4. Validar que haya datos para insertar
            if (empty($data)) {
                return ApiResponse::error('No se enviaron datos válidos', 400);
            }

            // 5. Verificar si viene un ID (UPDATE) o no (CREATE)
            $id = $request->input('id');

            if ($id) {
                // 🔄 UPDATE: Actualizar registro existente
                $updated = DB::table($tabla)
                    ->where('id', $id)
                    ->update($data);

                if ($updated) {
                    $record = DB::table($tabla)->find($id);
                    return ApiResponse::success($record, 'Registro actualizado exitosamente');
                } else {
                    return ApiResponse::error('No se encontró el registro con ID: ' . $id, 404);
                }
            } else {
                // ➕ CREATE: Insertar nuevo registro
                $newId = DB::table($tabla)->insertGetId($data);
                $newRecord = DB::table($tabla)->find($newId);

                return ApiResponse::success($newRecord, 'Registro creado exitosamente', 201);
            }
        } catch (\Exception $e) {
            Log::error('Error al crear/actualizar en ' . $tabla . ': ' . $e->getMessage());
            return ApiResponse::error('Error al procesar el registro: ' . $e->getMessage(), 500);
        }
    }
}
