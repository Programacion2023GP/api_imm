<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    // Desactivar timestamps automáticos (created_at, updated_at)
    public $timestamps = false;

    // Nombre de la tabla
    protected $table = 'eventos';

    // Campos asignables en masa (opcional, ya que usas DB::table)
    protected $fillable = [
        'fecha_realizacion',
        'id_aerea_organizadora',
        'id_tipo_actividad',
        'tema_central',
        'ponente_facilitador',
        'lugar',
        'duracion_estimada',
        'id_user_created',
        'numero_asistentes',
        'sexo',
        'edad',
        'persona_discapacidad',
        'poblacion_indigena',
        'poblacion_migrante',
        'poblacion_afrodescendiente',
        'comunidad_lgbtq',
        'otro',
        'especifique',
        'comentarios',
        'id_seguimiento_control',
        'id_responsable_seguimiento',
        'acciones_programadas',
        'fecha_proxima',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    // Casting de tipos
    protected $casts = [
        'fecha_realizacion' => 'date',
        'fecha_proxima' => 'date',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'persona_discapacidad' => 'boolean',
        'poblacion_indigena' => 'boolean',
        'poblacion_migrante' => 'boolean',
        'poblacion_afrodescendiente' => 'boolean',
        'comunidad_lgbtq' => 'boolean',
        'otro' => 'boolean',
        'numero_asistentes' => 'integer',
        'edad' => 'integer',
        'id_user_created' => 'integer',
        'id_seguimiento_control' => 'integer',
        'id_responsable_seguimiento' => 'integer',
    ];
}
