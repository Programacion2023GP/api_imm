<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessJuridic extends Model
{
    protected $table = 'process_juridic';

    protected $fillable = [
        'id_evaluaciones_juridicas',
        'id_tipo_caso_incidente',
        'actor',
        'expediente',
        'juzgado',
        'fecha_presentacion',
        'comentarios_presentacion',
        'fecha_radicacion',
        'comentarios_radicacion',
        'fecha_audiencia',
        'comentarios_audiencia',
        'fecha_exhorto',
        'comentarios_exhorto',
        'fecha_oficios',
        'comentarios_oficio',
        'tipo_promocion',
        'comentarios_promocion',
        'fecha_sentencia',
        'comentarios_sentencia'
    ];

    protected $casts = [
        'fecha_presentacion' => 'date',
        'fecha_radicacion' => 'date',
        'fecha_audiencia' => 'date',
        'fecha_exhorto' => 'date',
        'fecha_oficios' => 'date',
        'fecha_sentencia' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public $timestamps = true;

    // NO hay relaciones belongsTo
}
