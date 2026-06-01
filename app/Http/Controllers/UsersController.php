<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public function createOrUpdate(Request $request)
    {
        try {
            $id = ($request->id && $request->id > 0) ? $request->id : null;

            $data = [
                'usuario' => $request->usuario,
                'nombre_completo' => $request->nombre_completo,
                'id_rol' => $request->id_rol,
                'activo' =>  true,
            ];

            // Solo actualizar la contraseña si se proporciona una nueva
            if ($request->filled('password') && !empty($request->password)) {
                $data['password'] = Hash::make($request->password);
            }

            $usuario = User::updateOrCreate(
                ['id' => $id],
                $data
            );

            if ($usuario->wasRecentlyCreated) {
                $message = "Usuario creado correctamente";
            } elseif ($usuario->wasChanged()) {
                $message = "Usuario actualizado correctamente";
            } else {
                $message = "No hubo cambios";
            }

            return ApiResponse::success($usuario, $message);
        } catch (Exception $e) {
            Log::info("usuarios save: " . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
    public function index(Request $request)
    {
        try {
            $permisos = User::all();

            return ApiResponse::success($permisos, 'lista de usuarios');
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
    public function login(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('usuario', $request->usuario)->where('activo',1)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Credenciales incorrectas', 401);
        }
        $permisos = DB::table('roles_permisos')
            ->join('permisos', 'permisos.id', '=', 'roles_permisos.id_permiso')
            ->join('usuarios', 'usuarios.id_rol', '=', 'roles_permisos.id_rol')

            ->where('usuarios.id', $user->id)
            ->pluck('permisos.nombre_permiso');        // Crear token
        $token = $user->createToken('auth_token', $permisos->toArray())->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'token' => $token,
            'permisos' => $permisos,
            'token_type' => 'Bearer',
        ], 'Login exitoso');
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout exitoso');
    }
}
