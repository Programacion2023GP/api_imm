<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use App\Models\Roles;
use App\Models\Roles_Permisos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolesController extends Controller
{
    public function createOrUpdate(Request $request)
    {
        try {
            $id = ($request->id && $request->id > 0) ? $request->id : null;

            $rol = Roles::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $request->name,
                    'nombre_rol' => $request->nombre_rol,
                    'descripcion' => $request->descripcion,
                    'activo' => $request->activo ?? true, // Valor por defecto
                ]
            );
            if ($rol->wasRecentlyCreated) {
                $message = "Se creó correctamente";
            } elseif ($rol->wasChanged()) {
                $message = "Se actualizó correctamente";
            } else {
                $message = "No hubo cambios";
            }

            return ApiResponse::success($rol, $message);
        } catch (Exception $e) {
            Log::info("rol save: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }

    public function unChangePermissions(Request $request)
    {
        try {
            $id = ($request->id && $request->id > 0) ? $request->id : null;

            if (!$id) {
                return ApiResponse::error('ID de rol no válido', 400);
            }

            // Obtener los permisos (asumiendo que viene un array)
            $permissions = $request->permissions;

            if (!is_array($permissions) || empty($permissions)) {
                return ApiResponse::error('No se enviaron permisos válidos', 400);
            }

            // 1. Borrar todos los permisos existentes para este rol
            Roles_Permisos::where('id_rol', $id)->delete();

            // 2. Insertar los nuevos permisos
            $nuevosPermisos = [];
            foreach ($permissions as $id_permiso) {
                $nuevosPermisos[] = [
                    'id_rol' => $id,
                    'id_permiso' => $id_permiso,
                    'fecha_asignacion' => now(),
                ];
            }

            // Insertar todos los nuevos permisos
            $insertados = Roles_Permisos::insert($nuevosPermisos);

            if ($insertados) {
                $message = "Se actualizaron los permisos correctamente. " . count($nuevosPermisos) . " permisos asignados.";
            } else {
                $message = "No se pudieron asignar los permisos";
            }

            return ApiResponse::success($nuevosPermisos, $message);
        } catch (Exception $e) {
            Log::info("rol permisos save: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }



    


    public function index(Request $request)
    {
        try {

            $roles = Roles::select(
                'roles.*',
                DB::raw('GROUP_CONCAT(roles_permisos.id_permiso) as permissions')
            )
                ->leftJoin('roles_permisos', 'roles_permisos.id_rol', '=', 'roles.id')
                ->groupBy('roles.id')
                ->get()
                ->map(function ($role) {

                    $role->permissions = $role->permissions
                        ? array_map('intval', explode(',', $role->permissions))
                        : [];

                    return $role;
                });

            return ApiResponse::success($roles, 'lista de roles');
        } catch (Exception $e) {

            Log::info("rol index: " . $e->getMessage());

            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
    public function unChange(Request $request)
    {
        try {
            $permiso = Roles::findOrFail($request->id);

            $permiso->update([
                'activo' => !$permiso->activo  // o usa 0/1 si es entero: $permiso->activo == 1 ? 0 : 1
            ]);

            $estado = $permiso->activo ? 'activado' : 'desactivado';

            return ApiResponse::success(null, "Se ha {$estado} el rol correctamente");
        } catch (Exception $e) {
            Log::info("roles unChange: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
}
