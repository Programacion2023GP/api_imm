<?php
// app/Http/Controllers/EvaluacionPsicologicaController.php

namespace App\Http\Controllers;

use App\Models\EvaluacionPsicologica;
use App\Models\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EvaluacionPsicologicaController extends Controller
{
    // Obtener todas las evaluaciones o una específica
    public function index(Request $request)
    {
        try {
            if ($request->has('id')) {
                $evaluacion = $this->getEvaluacionById($request->id);

                if (!$evaluacion) {
                    return ApiResponse::error('Evaluación no encontrada', 404);
                }

                return ApiResponse::success($evaluacion, 'Evaluación obtenida correctamente');
            } else {
                $evaluaciones = $this->getAllEvaluaciones();
                return ApiResponse::success($evaluaciones, 'Evaluaciones obtenidas correctamente');
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Obtener catálogos para formulario
    public function catalogos()
    {
        try {
            $problematicas = DB::table('problematicas_abordadas')
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            $violencias = DB::table('violencias_asociadas')
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            return ApiResponse::success([
                'problematicas' => $problematicas,
                'violencias' => $violencias
            ], 'Catálogos obtenidos correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Crear o actualizar evaluación
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_alta' => 'required|date',
            'id_responsable' => 'required|integer',
            'id_entrevista' => 'required|integer|exists:entrevistas,id',
            // 'id_problematica_abordada' => 'required|array|min:1',
            // 'id_problematica_abordada.*' => 'integer|exists:problematicas_abordadas,id',
            // 'id_violencia_asociada' => 'required|array|min:1',
            // 'id_violencia_asociada.*' => 'integer|exists:violencias_asociadas,id',
            'especifique_problematica_abordada' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError('Error de validación '. $validator->errors());
        }

        try {
            DB::beginTransaction();
            if ($request->id==0) {
                $existeEvaluacion = DB::table('evaluaciones_psicologicas')
                    ->where('id_entrevista', $request->id_entrevista)
                    ->exists();

                if ($existeEvaluacion) {
                    DB::rollBack();
                    return ApiResponse::error(
                        'Esta usuaria ya tiene una evaluación psicológica activa. No se puede crear otra.',
                        500 // Conflict
                    );
                }
            }
            $evaluationId = $request->id;

            if ($request->id) {
                // Actualizar evaluación
                DB::table('evaluaciones_psicologicas')
                    ->where('id', $request->id)
                    ->update([
                        'fecha_alta' => $request->fecha_alta,
                        'id_responsable' => $request->id_responsable,
                        'id_entrevista' => $request->id_entrevista,
                        'especifique_problematica_abordada' => $request->especifique_problematica_abordada,
                        'activo' => 1
                    ]);

                // Eliminar relaciones existentes
                DB::table('evaluaciones_problematicas')->where('id_evaluacion', $request->id)->delete();
                DB::table('evaluaciones_violencias')->where('id_evaluacion', $request->id)->delete();
            } else {
                // Insertar nueva evaluación
                $evaluationId = DB::table('evaluaciones_psicologicas')->insertGetId([
                    'fecha_alta' => $request->fecha_alta,
                    'id_responsable' => $request->id_responsable,
                    'id_entrevista' => $request->id_entrevista,
                    'especifique_problematica_abordada' => $request->especifique_problematica_abordada,
                    'activo' => 1
                ]);
            }

            // Insertar problemáticas
            foreach ($request->id_problematica_abordada as $idProblematica) {
                DB::table('evaluaciones_problematicas')->insert([
                    'id_evaluacion' => $evaluationId,
                    'id_problematica' => $idProblematica
                ]);
            }

            // Insertar violencias
            foreach ($request->id_violencia_asociada as $idViolencia) {
                DB::table('evaluaciones_violencias')->insert([
                    'id_evaluacion' => $evaluationId,
                    'id_violencia' => $idViolencia
                ]);
            }

            DB::commit();

            $message = $request->id ? 'Evaluación actualizada correctamente' : 'Evaluación creada correctamente';
            return ApiResponse::success(['id' => $evaluationId], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }

    // Eliminar evaluación
    public function destroy(Request $request)
    {
        try {
            $id= $request->id;
            DB::beginTransaction();

            // Verificar si existe
            $evaluacion = DB::table('evaluaciones_psicologicas')->where('id', $id)->first();

            if (!$evaluacion) {
                return ApiResponse::error('Evaluación no encontrada', 404);
            }

            // Eliminar relaciones
            DB::table('evaluaciones_problematicas')->where('id_evaluacion', $id)->delete();
            DB::table('evaluaciones_violencias')->where('id_evaluacion', $id)->delete();

            // Eliminar evaluación
            DB::table('evaluaciones_psicologicas')->where('id', $id)->delete();

            DB::commit();

            return ApiResponse::success(null, 'Evaluación eliminada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Métodos privados para organizar la lógica
    private function getEvaluacionById($id)
    {
        $evaluacion = DB::table('evaluaciones_psicologicas as ep')
            ->select(
                'ep.id',
                'ep.fecha_alta',
                'ep.id_responsable',
                'ep.id_entrevista',
                'ep.especifique_problematica_abordada',
                'ep.activo'
            )
            ->where('ep.id', $id)
            ->first();

        if (!$evaluacion) {
            return null;
        }

        // Obtener problemáticas
        $problematicas = DB::table('evaluaciones_problematicas as ep')
            ->join('problematicas_abordadas as pa', 'ep.id_problematica', '=', 'pa.id')
            ->select('pa.id', 'pa.nombre')
            ->where('ep.id_evaluacion', $id)
            ->get();

        // Obtener violencias
        $violencias = DB::table('evaluaciones_violencias as ev')
            ->join('violencias_asociadas as va', 'ev.id_violencia', '=', 'va.id')
            ->select('va.id', 'va.nombre')
            ->where('ev.id_evaluacion', $id)
            ->get();

        // Obtener responsable
        $responsable = DB::table('users')
            ->select('id', 'name')
            ->where('id', $evaluacion->id_responsable)
            ->first();

        // Obtener entrevista
        $entrevista = DB::table('entrevistas')
            ->select('id', 'fecha', 'id_usuario')
            ->where('id', $evaluacion->id_entrevista)
            ->first();

        return [
            'id' => $evaluacion->id,
            'fecha_alta' => $evaluacion->fecha_alta,
            'id_responsable' => $evaluacion->id_responsable,
            'responsable_nombre' => $responsable ? $responsable->name : null,
            'id_entrevista' => $evaluacion->id_entrevista,
            'entrevista' => $entrevista,
            'especifique_problematica_abordada' => $evaluacion->especifique_problematica_abordada,
            'activo' => $evaluacion->activo,
            'id_problematica_abordada' => $problematicas->pluck('id')->toArray(),
            'problematicas_nombres' => $problematicas->pluck('nombre')->toArray(),
            'id_violencia_asociada' => $violencias->pluck('id')->toArray(),
            'violencias_nombres' => $violencias->pluck('nombre')->toArray()
        ];
    }

    private function getAllEvaluaciones()
    {
        $evaluaciones = DB::table('evaluaciones_psicologicas as ep')
            ->select(
                'ep.id',
                'ep.fecha_alta',
                'ep.id_responsable',
                'ep.id_entrevista',
                'ep.especifique_problematica_abordada',
                'ep.activo'
            )
            ->orderBy('ep.id', 'desc')
            ->get();

        foreach ($evaluaciones as $evaluacion) {
            $problematicas = DB::table('evaluaciones_problematicas as ep')
                ->join('problematicas_abordadas as pa', 'ep.id_problematica', '=', 'pa.id')
                ->select('pa.id', 'pa.nombre')
                ->where('ep.id_evaluacion', $evaluacion->id)
                ->get();

            $violencias = DB::table('evaluaciones_violencias as ev')
                ->join('violencias_asociadas as va', 'ev.id_violencia', '=', 'va.id')
                ->select('va.id', 'va.nombre')
                ->where('ev.id_evaluacion', $evaluacion->id)
                ->get();

            $responsable = DB::table('users')
                ->select('name')
                ->where('id', $evaluacion->id_responsable)
                ->first();

            $evaluacion->responsable_nombre = $responsable ? $responsable->name : null;
            $evaluacion->id_problematica_abordada = $problematicas->pluck('id')->toArray();
            $evaluacion->problematicas_nombres = $problematicas->pluck('nombre')->toArray();
            $evaluacion->id_violencia_asociada = $violencias->pluck('id')->toArray();
            $evaluacion->violencias_nombres = $violencias->pluck('nombre')->toArray();
        }

        return $evaluaciones;
    }
    public function agenda(Request $request)
    {
        try {
            // 1. Obtener las evaluaciones principales
            $evaluaciones = DB::table('evaluaciones_psicologicas as ep')
                ->leftJoin('entrevistas as e', 'e.id', '=', 'ep.id_entrevista')
                ->leftJoin('usuarios as u', 'u.id', '=', 'ep.id_responsable') // ✅ JOIN con usuarios

                ->select(
                    'ep.*',
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
                    'e.entre_calles',
                    'e.referencias',
                    'e.zona',
                    'u.nombre_completo'
                );

            // Filtrar por rol de psicólogo
            if (Auth::user()->id_rol == 4) {
                $evaluaciones = $evaluaciones->where('ep.id_responsable', Auth::id());
            }

            $evaluaciones = $evaluaciones->get();

            // Obtener IDs de evaluaciones
            $evaluacionesIds = $evaluaciones->pluck('id')->toArray();

            if (!empty($evaluacionesIds)) {
                // Obtener todas las problemáticas agrupadas por evaluación
                $problematicasPorEvaluacion = DB::table('evaluaciones_problematicas')
                    ->whereIn('id_evaluacion', $evaluacionesIds)
                    ->select('id_evaluacion', 'id_problematica')
                    ->get()
                    ->groupBy('id_evaluacion')
                    ->map(function ($items) {
                        return $items->pluck('id_problematica')->toArray();
                    });

                // Obtener todas las violencias agrupadas por evaluación
                $violenciasPorEvaluacion = DB::table('evaluaciones_violencias')
                    ->whereIn('id_evaluacion', $evaluacionesIds)
                    ->select('id_evaluacion', 'id_violencia')
                    ->get()
                    ->groupBy('id_evaluacion')
                    ->map(function ($items) {
                        return $items->pluck('id_violencia')->toArray();
                    });

                // Asignar los arrays de IDs a cada evaluación
                foreach ($evaluaciones as $evaluacion) {
                    $evaluacion->id_problematica_abordada = $problematicasPorEvaluacion[$evaluacion->id] ?? [];
                    $evaluacion->id_violencia_asociada = $violenciasPorEvaluacion[$evaluacion->id] ?? [];
                }
            } else {
                // Si no hay evaluaciones, agregar arrays vacíos a la colección para mantener consistencia
                $evaluaciones = $evaluaciones->map(function ($evaluacion) {
                    $evaluacion->id_problematica_abordada = [];
                    $evaluacion->id_violencia_asociada = [];
                    return $evaluacion;
                });
            }

            return ApiResponse::success($evaluaciones, 'Lista de espera');
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error');
        }
    }
}
