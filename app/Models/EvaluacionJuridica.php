<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionJuridica extends Model
{
    protected $table = 'evaluaciones_juridicas';

    protected $fillable = [
        'id_entrevista',
        'fecha_apertura',
        'id_responsable',
        'fecha_asesoria',
        'hechos',
        'id_tipo_asesoria',
        'id_estatus_caso',
        'activo'
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_asesoria' => 'date',
        'activo' => 'boolean'
    ];

    // Sin relaciones Eloquent
    public $timestamps = false;
}
