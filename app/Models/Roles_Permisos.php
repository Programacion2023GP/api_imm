<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles_Permisos extends Model
{
    protected $table = 'roles_permisos';

    // ✅ Agrega esto - lista de campos que pueden ser asignados masivamente
    protected $fillable = [
        'id_rol_permiso',
        'id_rol',
        'id_permiso',
        'fecha_asignacion'
    ];

    // Opcional: convertir activo a booleano automáticamente
    protected $casts = [
        // 'activo' => 'boolean',
    ];
}
