<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    protected $table = 'roles';

    // ✅ Agrega esto - lista de campos que pueden ser asignados masivamente
    protected $fillable = [
        'nombre_rol',
        'descripcion',
        'activo'
    ];

    // Opcional: convertir activo a booleano automáticamente
    protected $casts = [
        'activo' => 'boolean',
    ];
}
