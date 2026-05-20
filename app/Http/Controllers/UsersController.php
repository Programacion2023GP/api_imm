<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public function createOrUpdate(Request $request)
    {
        try {
            $id = ($request->id && $request->id > 0) ? $request->id : null;

            $permisos = User::updateOrCreate(
                ['id' => $id],
                [
                    'usuario' => $request->usuario,
                    'password' => Hash::make($request->password),
                    'nombre_completo' => $request->nombre_completo,
                    'id_rol' => $request->id_rol,
                    'activo' =>  true, // Valor por defecto
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
            Log::info("usuarios save: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
    public function index(Request $request)
    {
        try {
            $permisos = User::all();

            return ApiResponse::success($permisos, 'lista de permisos');
        } catch (Exception $e) {
            Log::info("usuarios index: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
    public function unChange(Request $request)
    {
        try {
            $permiso = User::findOrFail($request->id);

            $permiso->update([
                'activo' => !$permiso->activo  // o usa 0/1 si es entero: $permiso->activo == 1 ? 0 : 1
            ]);

            $estado = $permiso->activo ? 'activado' : 'desactivado';

            return ApiResponse::success(null, "Se ha {$estado} el usuario correctamente");
        } catch (Exception $e) {
            Log::info("usuarios unChange: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
