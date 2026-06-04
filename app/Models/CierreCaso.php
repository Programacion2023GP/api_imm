<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CierreCaso extends Model
{
    protected $table = 'cierres_caso';

    protected $fillable = [
        'evaluacion_psicologica_id',
        'diagnostico_final',
        'motivo',
        'otro_motivo',
        'fecha_cierre',
        'cerrado_en',
    ];

    protected $casts = [
        'fecha_cierre' => 'date',
        'cerrado_en' => 'datetime',
    ];

    public function evaluacionPsicologica()
    {
        return $this->belongsTo(EvaluacionPsicologica::class, 'evaluacion_psicologica_id');
    }
}
