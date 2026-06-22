<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Legal extends Model
{
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'evaluaciones_juridicas';

    /**
     * Indica si el modelo usa timestamps (created_at, updated_at).
     * La tabla no tiene estos campos, así que lo desactivamos.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_entrevista',
        'fecha_apertura',
        'id_responsable',
        'fecha_asesoria',
        'hechos',
        'id_tipo_asesoria',
        'id_estatus_caso',
        'activo',
        'fecha_acompanamiento',
        'fecha_denuncia',
        'nombre_imputado',
        'carpeta_investigacion',
        'causa_penal',
        'comentarios_procesales',
        'id_autoridad_emisora',
        'fecha_solicitud',
        'fecha_audiencia',
        'fecha_medida',
        'fecha_termino_medida',
        'id_tipo_medida',
        'descripcion_medida',
        'fecha_cierre',
        'id_motivo_cierre',
        'observaciones',
    ];
    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_asesoria' => 'date',
        'activo' => 'boolean',
        'fecha_acompanamiento' => 'date',
        'fecha_denuncia' => 'date',
        'fecha_solicitud' => 'date',
        'fecha_audiencia' => 'date',
        'fecha_medida' => 'date',
        'fecha_termino_medida' => 'date',
        'fecha_cierre' => 'date',
        'carpeta_investigacion' => 'int',
        'causa_penal' => 'int',
    ];
    /**
     * Relación con la entrevista (Módulo I).
     */
    

    /**
     * Relación con el tipo de asesoría.
     * Ajusta el modelo y la tabla según tu catálogo.
     */


    /**
     * Relación con el estatus del caso.
     * Ajusta el modelo y la tabla según tu catálogo.
     */
  
    /**
     * Relación muchos a muchos con los incidentes/tipos de caso.
     * Tabla pivote: evaluaciones_juridicas_incidentes
     */

}
