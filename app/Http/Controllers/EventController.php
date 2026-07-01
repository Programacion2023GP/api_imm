<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    // Obtener todos los eventos o uno específico
    public function index(Request $request)
    {
        try {
            if ($request->has('id')) {
                $evento = $this->getEventoById($request->id);

                if (!$evento) {
                    return ApiResponse::error('Evento no encontrado', 404);
                }

                return ApiResponse::success($evento, 'Evento obtenido correctamente');
            } else {
                $eventos = $this->getAllEventos();
                return ApiResponse::success($eventos, 'Eventos obtenidos correctamente');
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Obtener catálogos para formulario
    public function catalogos()
    {
        try {
            $tiposActividad = DB::table('tipos_actividad')
                ->select('id', 'nombre')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();

            $areasOrganizadoras = DB::table('areas_organizadoras')
                ->select('id', 'nombre')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();

            $responsables = DB::table('users')
                ->select('id', 'name', 'name as nombre_completo')
                ->orderBy('name')
                ->get();

            return ApiResponse::success([
                'tipos_actividad' => $tiposActividad,
                'areas_organizadoras' => $areasOrganizadoras,
                'responsables' => $responsables
            ], 'Catálogos obtenidos correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Crear o actualizar evento
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validación manual
            $requiredFields = ['fecha_realizacion', 'id_aerea_organizadora', 'id_tipo_actividad', 'tema_central', 'lugar', 'duracion_estimada'];
            foreach ($requiredFields as $field) {
                if (empty($request->$field)) {
                    DB::rollBack();
                    return ApiResponse::error("El campo '$field' es requerido", 400);
                }
            }

            // Preparar datos comunes
            $data = [
                'fecha_realizacion' => $request->fecha_realizacion,
                'id_aerea_organizadora' => $request->id_aerea_organizadora,
                'id_tipo_actividad' => $request->id_tipo_actividad,
                'tema_central' => $request->tema_central,
                'ponente_facilitador' => $request->ponente_facilitador,
                'lugar' => $request->lugar,
                'duracion_estimada' => $request->duracion_estimada,
                'numero_asistentes' => $request->numero_asistentes ?? 0,
                'sexo' => $request->sexo,
                'edad' => $request->edad,
                'persona_discapacidad' => $request->persona_discapacidad ? 1 : 0,
                'poblacion_indigena' => $request->poblacion_indigena ? 1 : 0,
                'poblacion_migrante' => $request->poblacion_migrante ? 1 : 0,
                'poblacion_afrodescendiente' => $request->poblacion_afrodescendiente ? 1 : 0,
                'comunidad_lgbtq' => $request->comunidad_lgbtq ? 1 : 0,
                'otro' => $request->otro ? 1 : 0,

                'especifique' => $request->especifique,
                'comentarios' => $request->comentarios,
                'id_seguimiento_control' => $request->id_seguimiento_control,
                'id_responsable_seguimiento' => $request->id_responsable_seguimiento,
                'acciones_programadas' => $request->acciones_programadas,
                'fecha_proxima' => $request->fecha_proxima,
            ];

            if ($request->has('id') && $request->id != 0) {
                // Actualizar evento existente
                $eventoId = $request->id;
                $existe = DB::table('eventos')->where('id', $eventoId)->exists();

                if (!$existe) {
                    DB::rollBack();
                    return ApiResponse::error('Evento no encontrado', 404);
                }

                // Actualizar sin timestamps automáticos
                DB::table('eventos')
                    ->where('id', $eventoId)
                    ->update(array_merge($data, ['fecha_actualizacion' => now()]));

                // Actualizar asistentes
                $this->actualizarAsistentes($eventoId, $request->asistentes ?? []);

                // Actualizar evidencias
                $this->actualizarEvidencias($eventoId, $request->evidencias ?? []);
            } else {
                // Crear nuevo evento
                $data['id_user_created'] = Auth::id();
                $data['fecha_creacion'] = now();
                $data['fecha_actualizacion'] = now();

                $eventoId = DB::table('eventos')->insertGetId($data);

                // Registrar asistentes
                if (!empty($request->asistentes)) {
                    $this->registrarAsistentes($eventoId, $request->asistentes);
                }

                // Registrar evidencias
                if (!empty($request->evidencias)) {
                    $this->registrarEvidencias($eventoId, $request->evidencias);
                }
            }

            DB::commit();

            $message = ($request->has('id') && $request->id != 0)
                ? 'Evento actualizado correctamente'
                : 'Evento creado correctamente';

            return ApiResponse::success(['id' => $eventoId], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Eliminar evento
    public function destroy(Request $request)
    {
        try {
            $id = $request->id;

            if (!$id) {
                return ApiResponse::error('ID de evento no proporcionado', 400);
            }

            DB::beginTransaction();

            $existe = DB::table('eventos')->where('id', $id)->exists();
            if (!$existe) {
                DB::rollBack();
                return ApiResponse::error('Evento no encontrado', 404);
            }

            // Eliminar relaciones
            DB::table('eventos_asistentes')->where('id_evento', $id)->delete();
            DB::table('evidencias_eventos')->where('id_evento', $id)->delete();
            DB::table('seguimiento_control')->where('id_evento', $id)->delete();

            // Eliminar evento
            DB::table('eventos')->where('id', $id)->delete();

            DB::commit();

            return ApiResponse::success(null, 'Evento eliminado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Obtener evento por ID con todos sus datos
    private function getEventoById($id)
    {
        $evento = DB::table('eventos as e')
            ->select(
                'e.*',
                'ta.nombre as tipo_actividad_nombre',
                'ao.nombre as area_organizadora_nombre',
                'u.name as user_created_nombre'
            )
            ->leftJoin('tipos_actividad as ta', 'e.id_tipo_actividad', '=', 'ta.id')
            ->leftJoin('areas_organizadoras as ao', 'e.id_aerea_organizadora', '=', 'ao.id')
            ->leftJoin('users as u', 'e.id_user_created', '=', 'u.id')
            ->where('e.id', $id)
            ->first();

        if (!$evento) {
            return null;
        }

        // Obtener asistentes
        $asistentes = DB::table('eventos_asistentes as ea')
            ->join('asistentes as a', 'ea.id_asistente', '=', 'a.id')
            ->select('a.*')
            ->where('ea.id_evento', $id)
            ->get();

        // Obtener evidencias
        $evidencias = DB::table('evidencias_eventos')
            ->where('id_evento', $id)
            ->pluck('evidencia')
            ->toArray();

        return [
            'id' => $evento->id,
            'fecha_realizacion' => $evento->fecha_realizacion,
            'id_aerea_organizadora' => $evento->id_aerea_organizadora,
            'area_organizadora_nombre' => $evento->area_organizadora_nombre,
            'id_tipo_actividad' => $evento->id_tipo_actividad,
            'tipo_actividad_nombre' => $evento->tipo_actividad_nombre,
            'tema_central' => $evento->tema_central,
            'ponente_facilitador' => $evento->ponente_facilitador,
            'lugar' => $evento->lugar,
            'duracion_estimada' => $evento->duracion_estimada,
            'id_user_created' => $evento->id_user_created,
            'user_created_nombre' => $evento->user_created_nombre,
            'numero_asistentes' => $evento->numero_asistentes,
            'sexo' => $evento->sexo,
            'edad' => $evento->edad,
            'persona_discapacidad' => (bool)$evento->persona_discapacidad,
            'poblacion_indigena' => (bool)$evento->poblacion_indigena,
            'poblacion_migrante' => (bool)$evento->poblacion_migrante,
            'poblacion_afrodescendiente' => (bool)$evento->poblacion_afrodescendiente,
            'comunidad_lgbtq' => (bool)$evento->comunidad_lgbtq,
            'otro' => (bool)$evento->otro,
            'especifique' => $evento->especifique,
            'asistentes' => $asistentes,
            'evidencias' => $evidencias,
            'comentarios' => $evento->comentarios,
            'id_seguimiento_control' => $evento->id_seguimiento_control,
            'id_responsable_seguimiento' => $evento->id_responsable_seguimiento,
            'acciones_programadas' => $evento->acciones_programadas,
            'fecha_proxima' => $evento->fecha_proxima,
            'fecha_creacion' => $evento->fecha_creacion,
            'fecha_actualizacion' => $evento->fecha_actualizacion
        ];
    }

    // Obtener todos los eventos
    private function getAllEventos()
    {
        $authUser = auth()->user();

        $query = DB::table('eventos as e')
            ->leftJoin('tipos_actividad as ta', 'e.id_tipo_actividad', '=', 'ta.id')
            ->leftJoin('areas_organizadoras as ao', 'e.id_aerea_organizadora', '=', 'ao.id')
            ->leftJoin('users as u', 'e.id_user_created', '=', 'u.id')
            ->select(
                'e.*',
                'ta.nombre as tipo_actividad_nombre',
                'ao.nombre as area_organizadora_nombre',
                'u.name as user_created_nombre'
            );

        // Si el rol del usuario autenticado NO es 1 ni 2, solo ve donde es responsable de seguimiento
        if (!in_array($authUser->id_rol, [1, 2])) {
            $query->where('e.id_responsable_seguimiento', $authUser->id);
        }

        $eventos = $query->orderBy('e.fecha_realizacion', 'desc')->get();

        foreach ($eventos as $evento) {
            // Contar asistentes
            $evento->total_asistentes = DB::table('eventos_asistentes')
                ->where('id_evento', $evento->id)
                ->count();

            // Obtener evidencias (solo los primeros 3 para no sobrecargar)
            $evento->evidencias = DB::table('evidencias_eventos')
                ->where('id_evento', $evento->id)
                ->limit(3)
                ->pluck('evidencia')
                ->toArray();

            // Obtener conteo por sexo
            $evento->conteo_sexo = DB::table('eventos_asistentes as ea')
                ->join('asistentes as a', 'ea.id_asistente', '=', 'a.id')
                ->select('a.sexo', DB::raw('count(*) as total'))
                ->where('ea.id_evento', $evento->id)
                ->groupBy('a.sexo')
                ->get();

            // Datos de seguimiento
            $evento->tiene_seguimiento = !is_null($evento->id_seguimiento_control);
        }

        return $eventos;
    }

    // Registrar asistentes para un evento
    private function registrarAsistentes($eventoId, $asistentes)
    {
        foreach ($asistentes as $asistenteData) {
            // Verificar si el asistente ya existe
            $asistente = DB::table('asistentes')
                ->where('nombre', $asistenteData['nombre'])
                ->where('colonia', $asistenteData['colonia'] ?? '')
                ->first();

            if (!$asistente) {
                // Crear nuevo asistente
                $asistenteId = DB::table('asistentes')->insertGetId([
                    'nombre' => $asistenteData['nombre'],
                    'sexo' => $asistenteData['sexo'],
                    'edad' => $asistenteData['edad'],
                    'colonia' => $asistenteData['colonia'] ?? null,
                    'dependencia' => $asistenteData['dependencia'] ?? null,
                ]);
            } else {
                $asistenteId = $asistente->id;
            }

            // Relacionar asistente con evento
            DB::table('eventos_asistentes')->insert([
                'id_evento' => $eventoId,
                'id_asistente' => $asistenteId,
            ]);
        }
    }

    // Actualizar asistentes (eliminar y recrear)
    private function actualizarAsistentes($eventoId, $asistentes)
    {
        // Eliminar relaciones existentes
        DB::table('eventos_asistentes')->where('id_evento', $eventoId)->delete();

        if (!empty($asistentes)) {
            $this->registrarAsistentes($eventoId, $asistentes);
        }
    }

    // Registrar evidencias
    private function registrarEvidencias($eventoId, $evidencias)
    {
        foreach ($evidencias as $evidencia) {
            DB::table('evidencias_eventos')->insert([
                'id_evento' => $eventoId,
                'evidencia' => $evidencia,
                'tipo_evidencia' => $this->detectarTipoEvidencia($evidencia),
            ]);
        }
    }

    // Actualizar evidencias (eliminar y recrear)
    private function actualizarEvidencias($eventoId, $evidencias)
    {
        DB::table('evidencias_eventos')->where('id_evento', $eventoId)->delete();

        if (!empty($evidencias)) {
            $this->registrarEvidencias($eventoId, $evidencias);
        }
    }

    // Detectar tipo de evidencia por extensión
    private function detectarTipoEvidencia($url)
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        $tipos = [
            'imagen' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'],
            'documento' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf'],
            'audio' => ['mp3', 'wav', 'ogg', 'aac', 'flac']
        ];

        foreach ($tipos as $tipo => $extensiones) {
            if (in_array($extension, $extensiones)) {
                return $tipo;
            }
        }

        return 'otro';
    }

    // Obtener estadísticas de eventos
    public function estadisticas(Request $request)
    {
        try {
            $fechaInicio = $request->fecha_inicio;
            $fechaFin = $request->fecha_fin;

            $query = DB::table('eventos');

            if ($fechaInicio && $fechaFin) {
                $query->whereBetween('fecha_realizacion', [$fechaInicio, $fechaFin]);
            }

            $totalEventos = $query->count();
            $totalAsistentes = DB::table('eventos_asistentes')->count();

            // Eventos por tipo de actividad
            $eventosPorTipo = DB::table('eventos')
                ->join('tipos_actividad', 'eventos.id_tipo_actividad', '=', 'tipos_actividad.id')
                ->select('tipos_actividad.nombre', DB::raw('count(*) as total'))
                ->groupBy('tipos_actividad.nombre')
                ->get();

            // Eventos por área organizadora
            $eventosPorArea = DB::table('eventos')
                ->join('areas_organizadoras', 'eventos.id_aerea_organizadora', '=', 'areas_organizadoras.id')
                ->select('areas_organizadoras.nombre', DB::raw('count(*) as total'))
                ->groupBy('areas_organizadoras.nombre')
                ->get();

            // Distribución por sexo
            $distribucionSexo = DB::table('asistentes')
                ->select('sexo', DB::raw('count(*) as total'))
                ->groupBy('sexo')
                ->get();

            // Promedio de asistentes por evento
            $promedioAsistentes = DB::table('eventos')
                ->avg('numero_asistentes');

            return ApiResponse::success([
                'total_eventos' => $totalEventos,
                'total_asistentes_generales' => $totalAsistentes,
                'promedio_asistentes_por_evento' => round($promedioAsistentes, 2),
                'eventos_por_tipo' => $eventosPorTipo,
                'eventos_por_area' => $eventosPorArea,
                'distribucion_sexo' => $distribucionSexo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ], 'Estadísticas obtenidas correctamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // Agenda de eventos próximos
    public function agenda(Request $request)
    {
        try {
            $eventos = DB::table('eventos as e')
                ->leftJoin('tipos_actividad as ta', 'e.id_tipo_actividad', '=', 'ta.id')
                ->leftJoin('areas_organizadoras as ao', 'e.id_aerea_organizadora', '=', 'ao.id')
                ->leftJoin('users as u', 'e.id_responsable_seguimiento', '=', 'u.id')
                ->select(
                    'e.*',
                    'ta.nombre as tipo_actividad_nombre',
                    'ao.nombre as area_organizadora_nombre',
                    'u.name as responsable_seguimiento_nombre'
                )
                ->where('e.fecha_realizacion', '>=', date('Y-m-d'))
                ->orderBy('e.fecha_realizacion', 'asc')
                ->limit(20)
                ->get();

            foreach ($eventos as $evento) {
                $evento->total_asistentes = DB::table('eventos_asistentes')
                    ->where('id_evento', $evento->id)
                    ->count();

                $evento->dias_restantes = now()->diffInDays($evento->fecha_realizacion);
            }

            return ApiResponse::success($eventos, 'Agenda de eventos próximos');
        } catch (\Exception $e) {
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }
}
