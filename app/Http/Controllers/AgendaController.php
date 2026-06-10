<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluacionPsicologica;
use App\Models\Cita;
use App\Models\CierreCaso;
use App\Models\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AgendaController extends Controller
{
    private function aplicarFiltroResponsable($query)
    {
        if (Auth::user()->id_rol == 4) {
            $query->where('e.id_responsable', Auth::id());
        }
        return $query;
    }

    /**
     * Obtener todos los datos iniciales para el frontend
     * GET /api/evaluacionpsicologica/agenda/datos-iniciales
     */
    public function obtenerDatosIniciales()
    {
        try {
            // Obtener personas activas con JOIN a entrevistas
            $query = DB::table('evaluaciones_psicologicas as e')
                ->join('entrevistas as ent', 'e.id_entrevista', '=', 'ent.id')
                ->join('usuarios as u', 'e.id_responsable', '=', 'u.id') // ✅ JOIN con usuarios para nombre del psicólogo

                ->select(
                    'e.id',
                    'ent.nombre',
                    'ent.id as folio',
                'ent.telefono',

                'u.nombre_completo'      // ✅ Nombre del psicólogo
            );

            $query = $this->aplicarFiltroResponsable($query);
            $personas = $query->get();

            // Obtener IDs de evaluaciones a las que tiene acceso
            $evaluacionesIds = DB::table('evaluaciones_psicologicas as e')
                ->when(Auth::user()->id_rol == 4, function ($q) {
                    return $q->where('e.id_responsable', Auth::id());
                })
                ->pluck('e.id');

            // Obtener citas
            // En el método obtenerDatosIniciales, modifica la transformación de citas:
            $citas = DB::table('citas as c')
                ->join('evaluaciones_psicologicas as e', 'c.evaluacion_psicologica_id', '=', 'e.id')
                ->join('entrevistas as ent', 'e.id_entrevista', '=', 'ent.id')
                ->join('usuarios as u', 'e.id_responsable', '=', 'u.id') // ✅ JOIN con usuarios para nombre del psicólogo
                ->whereIn('c.evaluacion_psicologica_id', $evaluacionesIds)
                ->select(
                    'c.id',
                    'c.evaluacion_psicologica_id as personaId',
                    'c.fecha',
                    'c.hora',
                    'c.duracion',
                    'c.asistio',
                    'c.notas_seguimiento as notasSeguimiento',
                    'ent.id as folio',
                'c.primeravez', // 👈 AGREGAR ESTA LÍNEA

                'ent.telefono',
                // ✅ Folio desde entrevistas
                'u.nombre_completo'      // ✅ Nombre del psicólogo
                )
                ->get()
                ->map(function ($c) {
                    return [
                        'id' => (string) $c->id,
                        'personaId' => $c->personaId,
                        'folio' => $c->folio,
                        'nombre_completo' => $c->nombre_completo ?? 'No asignado',
                        'fecha' => date('Y-m-d', strtotime($c->fecha)),
                        'hora' => date('H:i', strtotime($c->hora)),
                    'primeravez' => (bool) $c->primeravez, // 👈 AGREGAR

                    'duracion' => $c->duracion,
                    'telefono' => $c->telefono,

                    'asistio' => (bool) $c->asistio,
                        'notasSeguimiento' => $c->notasSeguimiento ?? '',
                    ];
                });

            // Obtener cierres de caso
            $cierres = CierreCaso::whereIn('evaluacion_psicologica_id', $evaluacionesIds)
                ->get()
                ->map(function ($c) {
                    return [
                        'personaId' => $c->evaluacion_psicologica_id,
                        'diagnosticoFinal' => $c->diagnostico_final,
                        'motivo' => $c->motivo,
                        'otroMotivo' => $c->otro_motivo,
                        'fechaCierre' => $c->fecha_cierre,
                        'cerradoEn' => $c->cerrado_en,
                    ];
                });

            return ApiResponse::success([
                'personas' => $personas,
                'citas' => $citas,
                'cierres' => $cierres
            ], 'Datos cargados correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al cargar datos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener estadísticas
     * GET /api/evaluacionpsicologica/agenda/estadisticas
     */
    public function obtenerEstadisticas()
    {
        try {
            // Obtener IDs de evaluaciones a las que tiene acceso
            $evaluacionesIds = DB::table('evaluaciones_psicologicas as e')
                ->when(Auth::user()->id_rol == 4, function ($q) {
                    return $q->where('e.id_responsable', Auth::id());
                })
                ->pluck('e.id');

            $totalCitas = Cita::whereIn('evaluacion_psicologica_id', $evaluacionesIds)->count();
            $totalPersonasActivas = DB::table('evaluaciones_psicologicas')
                ->whereIn('id', $evaluacionesIds)
                ->where('activo', 1)
                ->count();
            $totalCierres = CierreCaso::whereIn('evaluacion_psicologica_id', $evaluacionesIds)->count();

            $citasProximas = Cita::whereIn('evaluacion_psicologica_id', $evaluacionesIds)
                ->where('fecha', '>=', now()->format('Y-m-d'))
                ->where('fecha', '<=', now()->addDays(7)->format('Y-m-d'))
                ->count();

            $citasHoy = Cita::whereIn('evaluacion_psicologica_id', $evaluacionesIds)
                ->where('fecha', now()->format('Y-m-d'))
                ->count();

            return ApiResponse::success([
                'totalCitas' => $totalCitas,
                'totalPersonasActivas' => $totalPersonasActivas,
                'totalCierres' => $totalCierres,
                'citasProximas' => $citasProximas,
                'citasHoy' => $citasHoy,
            ], 'Estadísticas obtenidas correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener estadísticas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Crear o actualizar una cita
     * POST /api/evaluacionpsicologica/agenda/citas
     */
    public function guardarCita(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'personaId' => 'required|exists:evaluaciones_psicologicas,id',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
            'duracion' => 'required|integer|min:5|max:480',
            'asistio' => 'boolean',
            'primeravez' => 'boolean',

            'notasSeguimiento' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError(
                'Error de validación',
                $validator->errors()
            );
        }

        // Verificar acceso a la evaluación
        $evaluacion = DB::table('evaluaciones_psicologicas')
            ->where('id', $request->personaId)
            ->first();

        if (!$evaluacion) {
            return ApiResponse::error('Persona no encontrada', 404);
        }

        if (Auth::user()->id_rol == 4 && $evaluacion->id_responsable != Auth::id()) {
            return ApiResponse::error('No autorizado para gestionar citas de esta persona', 403);
        }

        try {
            DB::beginTransaction();

            $cita = Cita::updateOrCreate(
                ['id' => $request->id],
                [
                    'evaluacion_psicologica_id' => $request->personaId,
                    'fecha' => $request->fecha,
                    'hora' => $request->hora,
                    'duracion' => $request->duracion,
                    'asistio' => $request->asistio ?? false,
                    'primeravez' => $request->primeravez ?? false,

                    'notas_seguimiento' => $request->notasSeguimiento,
                ]
            );

            DB::commit();

            $message = $request->id ? 'Cita actualizada correctamente' : 'Cita creada correctamente';

            return ApiResponse::success([
                'id' => (string) $cita->id,
                'personaId' => $cita->evaluacion_psicologica_id,
                'fecha' => $cita->fecha,
                'hora' => $cita->hora,
                'duracion' => $cita->duracion,
                'asistio' => (bool) $cita->asistio,
                'notasSeguimiento' => $cita->notas_seguimiento ?? '',
            ], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Error al guardar la cita: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar una cita
     * DELETE /api/evaluacionpsicologica/agenda/citas/{id}
     */
    public function eliminarCita($id)
    {
        try {
            $cita = Cita::findOrFail($id);

            // Verificar acceso
            $evaluacion = DB::table('evaluaciones_psicologicas')
                ->where('id', $cita->evaluacion_psicologica_id)
                ->first();

            if (Auth::user()->id_rol == 4 && $evaluacion->id_responsable != Auth::id()) {
                return ApiResponse::error('No autorizado', 403);
            }

            $cita->delete();

            return ApiResponse::success(null, 'Cita eliminada correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al eliminar la cita: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mover una cita a otra fecha
     * POST /api/evaluacionpsicologica/agenda/citas/{id}/mover
     */
    public function moverCita(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nuevaFecha' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError(
                'Error de validación',
                $validator->errors()
            );
        }

        try {
            $cita = Cita::findOrFail($id);

            // Verificar acceso
            $evaluacion = DB::table('evaluaciones_psicologicas')
                ->where('id', $cita->evaluacion_psicologica_id)
                ->first();

            if (Auth::user()->id_rol == 4 && $evaluacion->id_responsable != Auth::id()) {
                return ApiResponse::error('No autorizado', 403);
            }

            $cita->update(['fecha' => $request->nuevaFecha]);

            return ApiResponse::success([
                'id' => (string) $cita->id,
                'nuevaFecha' => $cita->fecha
            ], 'Cita reagendada correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al mover la cita: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Guardar cierre de caso
     * POST /api/evaluacionpsicologica/agenda/cierres-caso
     */
    /**
     * Cerrar o reabrir un caso
     * Si se envía 'abrir' como acción, elimina el cierre
     * POST /api/evaluacionpsicologica/agenda/cierres-caso
     */
    public function guardarCierreCaso(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'personaId' => 'required|exists:evaluaciones_psicologicas,id',
            'diagnosticoFinal' => 'required|string',
            'otroMotivo' => 'required_if:motivo,Otro|nullable|string|min:3',
            'fechaCierre' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError('Error de validación', $validator->errors());
        }

        // Verificar acceso
        $evaluacion = DB::table('evaluaciones_psicologicas')
            ->where('id', $request->personaId)
            ->first();

        if (!$evaluacion) {
            return ApiResponse::error('Persona no encontrada', 404);
        }

        if (Auth::user()->id_rol == 4 && $evaluacion->id_responsable != Auth::id()) {
            return ApiResponse::error('No autorizado para cerrar el caso de esta persona', 403);
        }

        try {
            DB::beginTransaction();

            // Eliminar cierre anterior si existe
            CierreCaso::where('evaluacion_psicologica_id', $request->personaId)->delete();

            $cierre = CierreCaso::create([
                'evaluacion_psicologica_id' => $request->personaId,
                'diagnostico_final' => $request->diagnosticoFinal,
                'motivo' => $request->motivo,
                'otro_motivo' => $request->motivo === 'Otro' ? $request->otroMotivo : null,
                'fecha_cierre' => $request->fechaCierre,
                'cerrado_en' => now(),
            ]);

            DB::commit();

            return ApiResponse::success([
                'personaId' => $cierre->evaluacion_psicologica_id,
                'diagnosticoFinal' => $cierre->diagnostico_final,
                'motivo' => $cierre->motivo,
                'otroMotivo' => $cierre->otro_motivo,
                'fechaCierre' => $cierre->fecha_cierre,
                'cerradoEn' => $cierre->cerrado_en,
            ], 'Caso cerrado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Error al cerrar el caso: ', 500);
        }
    }

    /**
     * Reabrir caso (eliminar cierre)
     * DELETE /api/evaluacionpsicologica/agenda/cierrescaso/{personaId}
     */
    public function reabrirCaso($personaId)
    {
        try {
            // Verificar acceso
            $evaluacion = DB::table('evaluaciones_psicologicas')
                ->where('id', $personaId)
                ->first();

            if (!$evaluacion) {
                return ApiResponse::error('Persona no encontrada', 404);
            }

            if (Auth::user()->id_rol == 4 && $evaluacion->id_responsable != Auth::id()) {
                return ApiResponse::error('No autorizado para reabrir el caso de esta persona', 403);
            }

            // Verificar si existe un cierre
            $cierre = CierreCaso::where('evaluacion_psicologica_id', $personaId)->first();

            if (!$cierre) {
                return ApiResponse::error('No hay un caso cerrado para esta persona', 404);
            }

            // Eliminar el cierre
            $cierre->delete();

            return ApiResponse::success(null, 'Caso reabierto exitosamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al reabrir el caso: ' . $e->getMessage(), 500);
        }
    }
}
