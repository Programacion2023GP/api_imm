<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\ApiResponse;
use App\Models\Permisos;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PermisosController extends Controller
{
    public function createOrUpdate(Request $request)
    {
        try {
            $id = ($request->id && $request->id > 0) ? $request->id : null;

            $permisos = Permisos::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $request->name,
                    'nombre_permiso' => $request->nombre_permiso,
                    'descripcion' => $request->descripcion,
                    'modulo' => $request->modulo,
                    'activo' => $request->activo ?? true, // Valor por defecto
                ]
            );
            if ($permisos->wasRecentlyCreated) {
                $message = "Se creó correctamente";
            } elseif ($permisos->wasChanged()) {
                $message = "Se actualizó correctamente";
            } else {
                $message = "No hubo cambios";
            }

            return ApiResponse::success($permisos, $message);
        } catch (Exception $e) {
            Log::info("permisos save: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
    public function index(Request $request)
    {
        try {
            $permisos = Permisos::all();
           
            return ApiResponse::success($permisos, 'lista de permisos');
        } catch (Exception $e) {
            Log::info("permisos index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
    public function unChange(Request $request)
    {
        try {
            $permiso = Permisos::findOrFail($request->id);

            $permiso->update([
                'activo' => !$permiso->activo  // o usa 0/1 si es entero: $permiso->activo == 1 ? 0 : 1
            ]);

            $estado = $permiso->activo ? 'activado' : 'desactivado';

            return ApiResponse::success(null, "Se ha {$estado} el permiso correctamente");
        } catch (Exception $e) {
            Log::info("departamentos unChange: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
