<?php
// app/Models/EvaluacionPsicologica.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionPsicologica extends Model
{
    protected $table = 'evaluaciones_psicologicas';
    public $timestamps = false;

    protected $fillable = [
        'fecha_alta',
        'id_responsable',
        'id_entrevista',
        'especifique_problematica_abordada',
        'activo'
    ];
}
