<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'evaluacion_psicologica_id',
        'fecha',
        'hora',
        'duracion',
        'asistio',
        'notas_seguimiento',
        'primeravez'
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora' => 'string',
        'asistio' => 'boolean',
        'primeravez' => 'boolean',

    ];

    public function evaluacionPsicologica()
    {
        return $this->belongsTo(EvaluacionPsicologica::class, 'evaluacion_psicologica_id');
    }
}
