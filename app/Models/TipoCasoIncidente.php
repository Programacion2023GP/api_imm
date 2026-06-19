<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCasoIncidente extends Model
{
    protected $table = 'tipo_caso_incidente';
    protected $fillable = [
        'id',
        'nombre',
    ];
    public $timestamps = false;
}
