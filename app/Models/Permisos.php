<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permisos extends Model
{
    protected $table = 'permisos';

    // ✅ Agrega esto - lista de campos que pueden ser asignados masivamente
    protected $fillable = [
        'nombre_permiso',
        'descripcion',
        'modulo',
        'activo'
    ];

    // Opcional: convertir activo a booleano automáticamente
    protected $casts = [
        'activo' => 'boolean',
    ];
}
