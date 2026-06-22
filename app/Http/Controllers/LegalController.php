<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Legal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LegalController extends Controller
{
    // Obtener todas las evaluaciones jurídicas o una específica
    public function index(Request $request)
    {
        try {
            if ($request->has('id')) {
                $evaluacion = $this->getEvaluacionById($request->id);

                if (!$evaluacion) {
                    return ApiResponse::error('Evaluación jurídica no encontrada', 404);
                }

                return ApiResponse::success($evaluacion, 'Evaluación jurídica obtenida correctamente');
            } else {
                $evaluaciones = $this->getAllEvaluaciones();
                return ApiResponse::success($evaluaciones, 'Evaluaciones jurídicas obtenidas correctamente');
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Obtener catálogos para formulario
    public function catalogos()
    {
        try {
            $incidentes = DB::table('tipo_caso_incidente')
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            $tiposAsesoria = DB::table('tipo_asesoria')
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            $estatusCaso = DB::table('estatus_caso')
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            // Cambiar nombre_completo por name
            $responsables = DB::table('users')
                ->select('id', 'name', 'name as nombre_completo') // alias para compatibilidad con el frontend
                ->where('id_rol', 5)
                ->orderBy('name')
                ->get();

            return ApiResponse::success([
                'incidentes' => $incidentes,
                'tipos_asesoria' => $tiposAsesoria,
                'estatus_caso' => $estatusCaso,
                'responsables' => $responsables
            ], 'Catálogos obtenidos correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Crear o actualizar evaluación jurídica
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_apertura' => 'required|date',
            'fecha_asesoria' => 'required|date',
            'hechos' => 'required|string',
            'id_tipo_asesoria' => 'required|integer|exists:tipo_asesoria,id',
            'id_estatus_caso' => 'required|integer|exists:estatus_caso,id', // corregir tabla
            'id_casos_incidentes' => 'required|array|min:1',
            'id_casos_incidentes.*' => 'integer|exists:tipo_caso_incidente,id',
            'activo' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError('Error de validación: ' . $validator->errors());
        }

        try {
            DB::beginTransaction();

            if ($request->has('id') && $request->id != 0) {
                $legal = Legal::find($request->id);
                if (!$legal) {
                    DB::rollBack();
                    return ApiResponse::error('Evaluación jurídica no encontrada', 404);
                }

                $legal->update([
                    'id_entrevista' => $request->id_entrevista,
                    'fecha_apertura' => $request->fecha_apertura,
                    'id_responsable' => $request->id_responsable,
                    'fecha_asesoria' => $request->fecha_asesoria,
                    'hechos' => $request->hechos,
                    'id_tipo_asesoria' => $request->id_tipo_asesoria,
                    'id_estatus_caso' => $request->id_estatus_caso,
                    'activo' => $request->activo ?? true,
                    'fecha_acompanamiento' => $request->fecha_acompanamiento,
                    'fecha_denuncia' => $request->fecha_denuncia,
                    'nombre_imputado' => $request->nombre_imputado,
                    'carpeta_investigacion' => $request->carpeta_investigacion,
                    'causa_penal' => $request->causa_penal,
                    'comentarios_procesales' => $request->comentarios_procesales,
                    'id_autoridad_emisora' => $request->id_autoridad_emisora,
                    'fecha_solicitud' => $request->fecha_solicitud,
                    'fecha_audiencia' => $request->fecha_audiencia,
                    'fecha_medida' => $request->fecha_medida,
                    'fecha_termino_medida' => $request->fecha_termino_medida,
                    'id_tipo_medida' => $request->id_tipo_medida,
                    'descripcion_medida' => $request->descripcion_medida,
                    'fecha_cierre' => $request->fecha_cierre,
                    'id_motivo_cierre' => $request->id_motivo_cierre,
                    'observaciones' => $request->observaciones,
                ]);

                $evaluationId = $legal->id;

                DB::table('evaluaciones_juridicas_incidentes')
                    ->where('id_evaluacion_juridica', $evaluationId)
                    ->delete();
            } else {
                $existeEvaluacion = Legal::where('id_entrevista', $request->id_entrevista)->exists();

                if ($existeEvaluacion) {
                    DB::rollBack();
                    return ApiResponse::error(
                        'Esta entrevista ya tiene una evaluación jurídica. No se puede crear otra.',
                        409
                    );
                }

                $legal = Legal::create([
                    'id_entrevista' => $request->id_entrevista,
                    'fecha_apertura' => $request->fecha_apertura,
                    'id_responsable' => $request->id_responsable,
                    'fecha_asesoria' => $request->fecha_asesoria,
                    'hechos' => $request->hechos,
                    'id_tipo_asesoria' => $request->id_tipo_asesoria,
                    'id_estatus_caso' => $request->id_estatus_caso,
                    'activo' => $request->activo ?? true,
                    'fecha_acompanamiento' => $request->fecha_acompanamiento,
                    'fecha_denuncia' => $request->fecha_denuncia,
                    'nombre_imputado' => $request->nombre_imputado,
                    'carpeta_investigacion' => $request->carpeta_investigacion,
                    'causa_penal' => $request->causa_penal,
                    'comentarios_procesales' => $request->comentarios_procesales,
                    'id_autoridad_emisora' => $request->id_autoridad_emisora,
                    'fecha_solicitud' => $request->fecha_solicitud,
                    'fecha_audiencia' => $request->fecha_audiencia,
                    'fecha_medida' => $request->fecha_medida,
                    'fecha_termino_medida' => $request->fecha_termino_medida,
                    'id_tipo_medida' => $request->id_tipo_medida,
                    'descripcion_medida' => $request->descripcion_medida,
                    'fecha_cierre' => $request->fecha_cierre,
                    'id_motivo_cierre' => $request->id_motivo_cierre,
                    'observaciones' => $request->observaciones,
                ]);

                $evaluationId = $legal->id;
            }

            foreach ($request->id_casos_incidentes as $idIncidente) {
                DB::table('evaluaciones_juridicas_incidentes')->insert([
                    'id_evaluacion_juridica' => $evaluationId,
                    'id_tipo_incidente' => $idIncidente
                ]);
            }

            DB::commit();

            $message = ($request->has('id') && $request->id != 0)
                ? 'Evaluación jurídica actualizada correctamente'
                : 'Evaluación jurídica creada correctamente';

            return ApiResponse::success(['id' => $evaluationId], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Eliminar evaluación jurídica
    public function destroy(Request $request)
    {
        try {
            $id = $request->id;

            if (!$id) {
                return ApiResponse::error('ID de evaluación no proporcionado', 400);
            }

            DB::beginTransaction();

            $legal = Legal::find($id);

            if (!$legal) {
                DB::rollBack();
                return ApiResponse::error('Evaluación jurídica no encontrada', 404);
            }

            DB::table('evaluaciones_juridicas_incidentes')
                ->where('id_evaluacion_juridica', $id)
                ->delete();

            $legal->delete();

            DB::commit();

            return ApiResponse::success(null, 'Evaluación jurídica eliminada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    private function getEvaluacionById($id)
    {
        // Obtener la evaluación principal con todos los campos
        $evaluacion = DB::table('evaluaciones_juridicas as ej')
            ->select(
                'ej.id',
                'ej.id_entrevista',
                'ej.fecha_apertura',
                'ej.id_responsable',
                'ej.fecha_asesoria',
                'ej.hechos',
                'ej.id_tipo_asesoria',
                'ej.id_estatus_caso',
                'ej.activo',
                'ej.fecha_acompanamiento',
                'ej.fecha_denuncia',
                'ej.nombre_imputado',
                'ej.carpeta_investigacion',
                'ej.causa_penal',
                'ej.comentarios_procesales',
                'ej.id_autoridad_emisora',
                'ej.fecha_solicitud',
                'ej.fecha_audiencia',
                'ej.fecha_medida',
                'ej.fecha_termino_medida',
                'ej.id_tipo_medida',
                'ej.descripcion_medida',
                'ej.fecha_cierre',
                'ej.id_motivo_cierre',
                'ej.observaciones'
            )
            ->where('ej.id', $id)
            ->first();

        if (!$evaluacion) {
            return null;
        }

        // Obtener incidentes
        $incidentes = DB::table('evaluaciones_juridicas_incidentes as eji')
            ->join('tipo_caso_incidente as ctci', 'eji.id_tipo_incidente', '=', 'ctci.id')
            ->select('ctci.id', 'ctci.nombre')
            ->where('eji.id_evaluacion_juridica', $id)
            ->get();

        // Obtener nombres relacionados
        $responsable = DB::table('usuarios')
            ->select('nombre_completo')
            ->where('id', $evaluacion->id_responsable)
            ->first();

        $entrevista = DB::table('entrevistas')
            ->select('nombre', 'telefono', 'colonia', 'estado', 'municipio', 'calle', 'curp', 'zona', 'edad')
            ->where('id', $evaluacion->id_entrevista)
            ->first();

        $tipoAsesoria = DB::table('tipo_asesoria')
            ->select('nombre')
            ->where('id', $evaluacion->id_tipo_asesoria)
            ->first();

        $estatusCaso = DB::table('estatus_caso')
            ->select('nombre')
            ->where('id', $evaluacion->id_estatus_caso)
            ->first();

        $autoridadEmisora = DB::table('autoridad_emisora')
            ->select('nombre')
            ->where('id', $evaluacion->id_autoridad_emisora)
            ->first();

        $tipoMedida = DB::table('tipo_medida')
            ->select('nombre')
            ->where('id', $evaluacion->id_tipo_medida)
            ->first();

        $motivoCierre = DB::table('motivo_cierre')
            ->select('nombre')
            ->where('id', $evaluacion->id_motivo_cierre)
            ->first();

        // Construir respuesta sin IDs innecesarios
        return [
            // Datos de la evaluación (sin el id)
            'fecha_apertura' => $evaluacion->fecha_apertura,
            'fecha_asesoria' => $evaluacion->fecha_asesoria,
            'hechos' => $evaluacion->hechos,
            'activo' => $evaluacion->activo,
            'fecha_acompanamiento' => $evaluacion->fecha_acompanamiento,
            'fecha_denuncia' => $evaluacion->fecha_denuncia,
            'nombre_imputado' => $evaluacion->nombre_imputado,
            'carpeta_investigacion' => $evaluacion->carpeta_investigacion,
            'causa_penal' => $evaluacion->causa_penal,
            'comentarios_procesales' => $evaluacion->comentarios_procesales,
            'fecha_solicitud' => $evaluacion->fecha_solicitud,
            'fecha_audiencia' => $evaluacion->fecha_audiencia,
            'fecha_medida' => $evaluacion->fecha_medida,
            'fecha_termino_medida' => $evaluacion->fecha_termino_medida,
            'descripcion_medida' => $evaluacion->descripcion_medida,
            'fecha_cierre' => $evaluacion->fecha_cierre,
            'observaciones' => $evaluacion->observaciones,

            // Nombres relacionados
            'responsable_nombre' => $responsable ? $responsable->name : null,
            'tipo_asesoria_nombre' => $tipoAsesoria ? $tipoAsesoria->nombre : null,
            'estatus_caso_nombre' => $estatusCaso ? $estatusCaso->nombre : null,
            'autoridad_emisora_nombre' => $autoridadEmisora ? $autoridadEmisora->nombre : null,
            'tipo_medida_nombre' => $tipoMedida ? $tipoMedida->nombre : null,
            'motivo_cierre_nombre' => $motivoCierre ? $motivoCierre->nombre : null,

            // Datos de la entrevista (sin id)
            'entrevista_nombre' => $entrevista ? $entrevista->nombre : null,
            'telefono' => $entrevista ? $entrevista->telefono : null,
            'colonia' => $entrevista ? $entrevista->colonia : null,
            'estado' => $entrevista ? $entrevista->estado : null,
            'municipio' => $entrevista ? $entrevista->municipio : null,
            'calle' => $entrevista ? $entrevista->calle : null,
            'curp' => $entrevista ? $entrevista->curp : null,
            'zona' => $entrevista ? $entrevista->zona : null,
            'edad' => $entrevista ? $entrevista->edad : null,

            // Incidentes (solo nombres, sin IDs)
            'incidentes_nombres' => $incidentes->pluck('nombre')->toArray(),
        ];
    }

    // En LegalController.php - getAllEvaluaciones()

    private function getAllEvaluaciones()
    {
        $evaluaciones = DB::table('evaluaciones_juridicas as ej')
            ->leftJoin('entrevistas as e', 'e.id', '=', 'ej.id_entrevista')
            ->leftJoin('relacion as r', 'r.id', '=', 'e.id_vinculo_agresor')
            ->leftJoin('usuarios as us', 'u.id', '=', 'e.id_user_created')

            ->leftJoin('usuarios as u', 'u.id', '=', 'ej.id_responsable')
            ->leftJoin('tipo_asesoria as ta', 'ta.id', '=', 'ej.id_tipo_asesoria')
            ->leftJoin('estatus_caso as ec', 'ec.id', '=', 'ej.id_estatus_caso')
            ->leftJoin('autoridad_emisora as ae', 'ae.id', '=', 'ej.id_autoridad_emisora')
            ->leftJoin('tipo_medida as tm', 'tm.id', '=', 'ej.id_tipo_medida')
            ->leftJoin('motivo_cierre as mc', 'mc.id', '=', 'ej.id_motivo_cierre')
            ->select(
                'ej.id as folio',                    // folio = id de la evaluación
                'ej.id',
            'r.nombre as relacion_victima',
                'ej.id_entrevista',
                'ej.fecha_apertura',
                'ej.id_responsable',
                'ej.fecha_asesoria',
                'ej.hechos',
                'ej.id_tipo_asesoria',
                'ej.id_estatus_caso',
                'ej.activo',
                'ej.fecha_acompanamiento',
                'ej.fecha_denuncia',
                'ej.nombre_imputado',
                'ej.carpeta_investigacion',
                'ej.causa_penal',
                'ej.comentarios_procesales',
                'ej.id_autoridad_emisora',
                'ej.fecha_solicitud',
                'ej.fecha_audiencia',
                'ej.fecha_medida',
                'ej.fecha_termino_medida',
                'ej.id_tipo_medida',
                'ej.descripcion_medida',
                'ej.fecha_cierre',
                'ej.id_motivo_cierre',
                'ej.observaciones',
                'e.nombre_agresor',
                // Datos de la entrevista
                'e.nombre as entrevista_nombre',
                'e.telefono',
                'e.colonia',
                'e.estado',
                'e.municipio',
                'e.calle',
                'e.curp',
                'e.zona',
                'e.edad',
                // Nombres relacionados
                'us.nombre_completo as responsable_nombre',
                'ta.nombre as tipo_asesoria_nombre',
                'ec.nombre as estatus_caso_nombre',
                'ae.nombre as autoridad_emisora_nombre',
                'tm.nombre as tipo_medida_nombre',
                'mc.nombre as motivo_cierre_nombre'
            )
            ->orderBy('ej.id', 'desc');
            if (Auth::user()->id_rol == 4) {
                $evaluaciones = $evaluaciones->where('ej.id_responsable', Auth::id());
                }
        $evaluaciones = $evaluaciones->get();
        // Cargar incidentes y procesos para cada evaluación
        foreach ($evaluaciones as $evaluacion) {
            $incidentes = DB::table('evaluaciones_juridicas_incidentes as eji')
                ->join('tipo_caso_incidente as ctci', 'eji.id_tipo_incidente', '=', 'ctci.id')
                ->select('ctci.id', 'ctci.nombre')
                ->where('eji.id_evaluacion_juridica', $evaluacion->id)
                ->get();

            foreach ($incidentes as $incidente) {
                $proceso = DB::table('process_juridic')
                    ->where('id_evaluaciones_juridicas', $evaluacion->id)
                    ->where('id_tipo_caso_incidente', $incidente->id)
                    ->first();

                if ($proceso) {
                    $this->cargarEvidenciasProceso($proceso);
                }

                $incidente->proceso = $proceso;
            }

            $evaluacion->incidentes = $incidentes->toArray();
            $evaluacion->id_casos_incidentes = $incidentes->pluck('id')->toArray();
            $evaluacion->id_nombres_incidentes = $incidentes->pluck('nombre')->toArray();
        }

        return $evaluaciones;
    }

    /**
     * Carga las evidencias de un proceso y las devuelve como arrays de strings (URLs)
     */
    private function cargarEvidenciasProceso(&$proceso)
    {
        $tiposEvidencia = [
            'presentacion' => 'process_evidencias_presentacion',
            'radicacion'   => 'process_evidencias_radicacion',
            'audiencia'    => 'process_evidencias_audiencia',
            'exhorto'      => 'process_evidencias_exhorto',
            'oficio'       => 'process_evidencias_oficio',
            'promocion'    => 'process_evidencias_promocion',
            'sentencia'    => 'process_evidencias_sentencia'
        ];

        foreach ($tiposEvidencia as $key => $tabla) {
            // Obtener solo las URLs como array de strings
            $evidencias = DB::table($tabla)
                ->where('id_process_juridic', $proceso->id)
                ->pluck('evidencia_url') // ← Esto devuelve una colección de strings
                ->toArray(); // ← Convertir a array

            $proceso->{"evidencias_{$key}"} = $evidencias;
        }
    }

    // Agenda/lista con datos de entrevista
    public function agenda(Request $request)
    {
        try {
            $evaluaciones = DB::table('evaluaciones_juridicas as ej')
                ->leftJoin('entrevistas as e', 'e.id', '=', 'ej.id_entrevista')
                ->leftJoin('users as u', 'u.id', '=', 'ej.id_responsable')
                ->select(
                    'ej.*',
                    'e.id as folio',
                    'e.nombre',
                    'e.edad',
                    'e.telefono',
                    'e.codigo_postal',
                    'e.colonia',
                    'e.estado',
                    'e.municipio',
                    'e.localidad',
                    'e.calle',
                    'e.num_ext',
                    'e.num_int',
                    'u.name as responsable_nombre' // cambiar a name
                );

            if (Auth::user()->id_rol == 5) {
                $evaluaciones = $evaluaciones->where('ej.id_responsable', Auth::id());
            }

            $evaluaciones = $evaluaciones->orderBy('ej.fecha_asesoria', 'desc')->get();

            $evaluacionesIds = $evaluaciones->pluck('id')->toArray();

            if (!empty($evaluacionesIds)) {
                $incidentesPorEvaluacion = DB::table('evaluaciones_juridicas_incidentes')
                    ->whereIn('id_evaluacion_juridica', $evaluacionesIds)
                    ->join('tipo_caso_incidente', 'evaluaciones_juridicas_incidentes.id_tipo_incidente', '=', 'tipo_caso_incidente.id')
                    ->select('id_evaluacion_juridica', 'tipo_caso_incidente.id', 'tipo_caso_incidente.nombre')
                    ->get()
                    ->groupBy('id_evaluacion_juridica')
                    ->map(function ($items) {
                        return [
                            'ids' => $items->pluck('id')->toArray(),
                            'nombres' => $items->pluck('nombre')->toArray()
                        ];
                    });

                foreach ($evaluaciones as $evaluacion) {
                    $evaluacion->id_casos_incidentes = $incidentesPorEvaluacion[$evaluacion->id]['ids'] ?? [];
                    $evaluacion->incidentes_nombres = $incidentesPorEvaluacion[$evaluacion->id]['nombres'] ?? [];
                }
            } else {
                foreach ($evaluaciones as $evaluacion) {
                    $evaluacion->id_casos_incidentes = [];
                    $evaluacion->incidentes_nombres = [];
                }
            }

            return ApiResponse::success($evaluaciones, 'Lista de evaluaciones jurídicas');
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }
}
