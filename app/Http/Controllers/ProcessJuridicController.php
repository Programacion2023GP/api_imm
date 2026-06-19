<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\ProcessJuridic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessJuridicController extends Controller
{
    // ========================================================================
    // CONFIGURACIÓN DE LA BASE DE DATOS (AJUSTA SEGÚN TUS TABLAS)
    // ========================================================================

    /**
     * Retorna el mapeo de tipos de evidencia a nombres de tablas.
     * Ejemplo: 'presentacion' => 'evidencias_presentacion'
     */
    private function getTablasEvidencias(): array
    {
        return [
            'presentacion' => 'process_evidencias_presentacion',
            'radicacion'   => 'process_evidencias_radicacion',
            'audiencia'    => 'process_evidencias_audiencia',
            'exhorto'      => 'process_evidencias_exhorto',
            'oficio'       => 'process_evidencias_oficio',
            'promocion'    => 'process_evidencias_promocion',
            'sentencia'    => 'process_evidencias_sentencia',
        ];
    }

    /**
     * Nombre de la columna que relaciona la evidencia con el proceso.
     */
    private function getColumnaProcesoId(): string
    {
        return 'id_process_juridic';
    }

    /**
     * Columnas que se seleccionan al cargar evidencias.
     */
    private function getColumnasEvidencia(): array
    {
        return [
            'id',
            'evidencia_url',
            'nombre_original',
            'tipo_archivo',
            'tamano',
            'fecha_subida'
        ];
    }

    // ========================================================================
    // MÉTODOS PÚBLICOS
    // ========================================================================

    public function index(Request $request)
    {
        try {
            if ($request->has('id')) {
                $proceso = $this->getProcesoById($request->id);
                if (!$proceso) {
                    return ApiResponse::error('Proceso jurídico no encontrado', 404);
                }
                return ApiResponse::success($proceso, 'Proceso obtenido correctamente');
            } else {
                $procesos = $this->getAllProcesos();
                return ApiResponse::success($procesos, 'Procesos obtenidos correctamente');
            }
        } catch (\Exception $e) {
            Log::error('Error en index: ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    public function catalogos()
    {
        try {
            $evaluacionesJuridicas = DB::table('evaluaciones_juridicas')
                ->select('id', DB::raw("CONCAT('Evaluación #', id) as nombre"))
                ->orderBy('id', 'desc')
                ->get();

            $incidentesEvaluacion = DB::table('evaluaciones_juridicas_incidente')
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            $tiposPromocion = [
                ['id' => 'demanda', 'nombre' => 'Demanda'],
                ['id' => 'escrito', 'nombre' => 'Escrito'],
                ['id' => 'recurso', 'nombre' => 'Recurso'],
                ['id' => 'ampliacion', 'nombre' => 'Ampliación'],
                ['id' => 'desistimiento', 'nombre' => 'Desistimiento'],
                ['id' => 'otros', 'nombre' => 'Otros'],
            ];

            $responsables = DB::table('users')
                ->select('id', 'name', 'name as nombre_completo')
                ->where('id_rol', 5)
                ->orderBy('name')
                ->get();

            return ApiResponse::success([
                'evaluaciones_juridicas' => $evaluacionesJuridicas,
                'incidentes_evaluacion'  => $incidentesEvaluacion,
                'tipos_promocion'        => $tiposPromocion,
                'responsables'           => $responsables,
            ], 'Catálogos obtenidos correctamente');
        } catch (\Exception $e) {
            Log::error('Error en catalogos: ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_evaluaciones_juridicas' => 'required|integer|exists:evaluaciones_juridicas,id',
            'id_tipo_caso_incidente'    => 'required|integer|exists:tipo_caso_incidente,id',
            'actor'                     => 'required|string|max:255',
            'expediente'                => 'required|string|max:100',
            'juzgado'                   => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError('Error de validación: ' . $validator->errors());
        }

        try {
            DB::beginTransaction();

            // Buscar si ya existe un proceso con la misma evaluación e incidente
            $procesoExistente = ProcessJuridic::where('id_evaluaciones_juridicas', $request->id_evaluaciones_juridicas)
                ->where('id_tipo_caso_incidente', $request->id_tipo_caso_incidente)
                ->first();

            if ($procesoExistente) {
                // Actualizar el proceso
                $procesoExistente->update($this->getDatosProceso($request));
                $processId = $procesoExistente->id;
                $message = 'Proceso jurídico actualizado correctamente';

                // Sincronizar evidencias (solo reemplazar tipos con nuevos archivos)
                $this->syncEvidencias($processId, $request);
            } else {
                // Verificar duplicado por expediente
                if (ProcessJuridic::where('expediente', $request->expediente)->exists()) {
                    DB::rollBack();
                    return ApiResponse::error('Ya existe un proceso con el expediente ' . $request->expediente, 409);
                }

                // Crear nuevo proceso
                $proceso = ProcessJuridic::create($this->getDatosProceso($request));
                $processId = $proceso->id;
                $message = 'Proceso jurídico creado correctamente';

                // Guardar evidencias por primera vez
                $this->saveEvidenciasFiles($processId, $request);
            }

            DB::commit();

            return ApiResponse::success(['id' => $processId], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en store: ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $id = $request->id;
            if (!$id) {
                return ApiResponse::error('ID de proceso no proporcionado', 400);
            }

            DB::beginTransaction();

            $proceso = ProcessJuridic::find($id);
            if (!$proceso) {
                DB::rollBack();
                return ApiResponse::error('Proceso jurídico no encontrado', 404);
            }

            // Eliminar todas las evidencias (físicas y registros)
            $this->deleteEvidenciasFiles($id);
            $this->deleteEvidencias($id);

            $proceso->delete();

            DB::commit();

            return ApiResponse::success(null, 'Proceso jurídico eliminado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en destroy: ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    public function getByEvaluacion(Request $request)
    {
        try {
            $idEvaluacion = $request->id_evaluacion;
            if (!$idEvaluacion) {
                return ApiResponse::error('ID de evaluación jurídica no proporcionado', 400);
            }

            $procesos = DB::table('process_juridic')
                ->where('id_evaluaciones_juridicas', $idEvaluacion)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($procesos as $proceso) {
                $this->cargarEvidencias($proceso);
            }

            return ApiResponse::success($procesos, 'Procesos obtenidos correctamente');
        } catch (\Exception $e) {
            Log::error('Error en getByEvaluacion: ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    public function downloadEvidencia(Request $request)
    {
        try {
            $id = $request->id;
            $tipo = $request->tipo;
            $nombreArchivo = $request->nombre;

            if (!$id || !$tipo || !$nombreArchivo) {
                return ApiResponse::error('Faltan parámetros', 400);
            }

            $path = "process_juridic/{$id}/{$tipo}/{$nombreArchivo}";

            if (!Storage::disk('public')->exists($path)) {
                return ApiResponse::error('Archivo no encontrado', 404);
            }

            return Storage::disk('public')->download($path);
        } catch (\Exception $e) {
            Log::error('Error en downloadEvidencia: ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    public function agenda(Request $request)
    {
        try {
            $procesos = DB::table('process_juridic as pj')
                ->leftJoin('evaluaciones_juridicas as ej', 'ej.id', '=', 'pj.id_evaluaciones_juridicas')
                ->leftJoin('evaluaciones_juridicas_incidente as eji', 'eji.id', '=', 'pj.id_tipo_caso_incidente')
                ->select('pj.*', 'ej.id as id_evaluacion', 'eji.nombre as incidente_nombre')
                ->orderBy('pj.fecha_presentacion', 'desc')
                ->get();

            foreach ($procesos as $proceso) {
                $this->cargarEvidencias($proceso);
            }

            return ApiResponse::success($procesos, 'Lista de procesos jurídicos');
        } catch (\Exception $e) {
            Log::error('Error en agenda: ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    public function resumenPorEvaluacion(Request $request)
    {
        try {
            $resumen = DB::table('process_juridic')
                ->select(
                    'id_evaluaciones_juridicas',
                    DB::raw('COUNT(*) as total_procesos'),
                    DB::raw('COUNT(DISTINCT id_tipo_caso_incidente) as total_incidentes')
                )
                ->groupBy('id_evaluaciones_juridicas')
                ->orderBy('total_procesos', 'desc')
                ->get();

            return ApiResponse::success($resumen, 'Resumen de procesos por evaluación');
        } catch (\Exception $e) {
            Log::error('Error en resumenPorEvaluacion: ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error: ' . $e->getMessage(), 500);
        }
    }

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Construye el array de datos para crear o actualizar un proceso.
     */
    private function getDatosProceso(Request $request): array
    {
        return [
            'id_evaluaciones_juridicas' => $request->id_evaluaciones_juridicas,
            'id_tipo_caso_incidente'    => $request->id_tipo_caso_incidente,
            'actor'                     => $request->actor,
            'expediente'                => $request->expediente,
            'juzgado'                   => $request->juzgado,
            'fecha_presentacion'        => $request->fecha_presentacion,
            'comentarios_presentacion'  => $request->comentarios_presentacion,
            'fecha_radicacion'          => $request->fecha_radicacion,
            'comentarios_radicacion'    => $request->comentarios_radicacion,
            'fecha_audiencia'           => $request->fecha_audiencia,
            'comentarios_audiencia'     => $request->comentarios_audiencia,
            'fecha_exhorto'             => $request->fecha_exhorto,
            'comentarios_exhorto'       => $request->comentarios_exhorto,
            'fecha_oficios'             => $request->fecha_oficios,
            'comentarios_oficio'        => $request->comentarios_oficio,
            'tipo_promocion'            => $request->tipo_promocion,
            'comentarios_promocion'     => $request->comentarios_promocion,
            'fecha_sentencia'           => $request->fecha_sentencia,
            'comentarios_sentencia'     => $request->comentarios_sentencia,
        ];
    }

    private function getProcesoById($id)
    {
        $proceso = DB::table('process_juridic')->where('id', $id)->first();
        if (!$proceso) return null;

        $this->cargarEvidencias($proceso);

        $evaluacion = DB::table('evaluaciones_juridicas')->select('id')->where('id', $proceso->id_evaluaciones_juridicas)->first();
        $incidente  = DB::table('evaluaciones_juridicas_incidente')->select('id', 'nombre')->where('id', $proceso->id_tipo_caso_incidente)->first();

        $proceso->evaluacion = $evaluacion;
        $proceso->incidente  = $incidente;

        return $proceso;
    }

    private function getAllProcesos()
    {
        $procesos = DB::table('process_juridic')->orderBy('id', 'desc')->get();

        foreach ($procesos as $proceso) {
            $this->cargarEvidencias($proceso);

            $incidente = DB::table('evaluaciones_juridicas_incidente')
                ->select('nombre')
                ->where('id', $proceso->id_tipo_caso_incidente)
                ->first();

            $proceso->incidente_nombre = $incidente ? $incidente->nombre : null;
        }

        return $procesos;
    }

    /**
     * Carga las evidencias de un proceso y las asigna como propiedades dinámicas.
     */
    private function cargarEvidencias(&$proceso)
    {
        $tablas = $this->getTablasEvidencias();
        $colProceso = $this->getColumnaProcesoId();
        $columnas = $this->getColumnasEvidencia();

        foreach ($tablas as $key => $tabla) {
            $evidencias = DB::table($tabla)
                ->where($colProceso, $proceso->id)
                ->select($columnas)
                ->get();

            $proceso->{"evidencias_{$key}"} = $evidencias->toArray();
        }
    }

    /**
     * Sincroniza las evidencias: solo reemplaza los tipos para los cuales se hayan subido nuevos archivos.
     */
    private function syncEvidencias($processId, $request)
    {
        $tablas = $this->getTablasEvidencias();

        foreach ($tablas as $key => $tabla) {
            $campo = "evidencias_{$key}";

            if ($request->hasFile($campo)) {
                $files = $request->file($campo);
                if (!is_array($files)) {
                    $files = [$files];
                }

                // Verificar si al menos un archivo es válido
                $hasValidFile = false;
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $hasValidFile = true;
                        break;
                    }
                }

                if ($hasValidFile) {
                    // 1. Eliminar evidencias antiguas de este tipo (físicas y registros)
                    $this->deleteEvidenciasFilesByType($processId, $key);
                    $this->deleteEvidenciasByType($processId, $key);

                    // 2. Guardar los nuevos archivos para este tipo
                    $this->saveEvidenciasFilesByType($processId, $request, $key);
                }
            }
            // Si no hay archivos, no se toca nada -> se mantienen las evidencias existentes
        }
    }

    /**
     * Elimina los archivos físicos de un tipo específico de evidencia.
     */
    private function deleteEvidenciasFilesByType($processId, $tipo)
    {
        $tablas = $this->getTablasEvidencias();
        if (!isset($tablas[$tipo])) {
            return;
        }
        $tabla = $tablas[$tipo];
        $colProceso = $this->getColumnaProcesoId();

        $evidencias = DB::table($tabla)->where($colProceso, $processId)->get();
        foreach ($evidencias as $evidencia) {
            $this->deleteFileIfLocal($evidencia->evidencia_url);
        }
    }

    /**
     * Elimina los registros de base de datos de un tipo específico de evidencia.
     */
    private function deleteEvidenciasByType($processId, $tipo)
    {
        $tablas = $this->getTablasEvidencias();
        if (!isset($tablas[$tipo])) {
            return;
        }
        $tabla = $tablas[$tipo];
        $colProceso = $this->getColumnaProcesoId();
        DB::table($tabla)->where($colProceso, $processId)->delete();
    }

    /**
     * Guarda los archivos de un tipo específico de evidencia.
     */
    private function saveEvidenciasFilesByType($processId, $request, $tipo)
    {
        $tablas = $this->getTablasEvidencias();
        if (!isset($tablas[$tipo])) {
            return;
        }
        $tabla = $tablas[$tipo];
        $colProceso = $this->getColumnaProcesoId();
        $campo = "evidencias_{$tipo}";

        $files = $request->file($campo);
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $nombreOriginal = $file->getClientOriginalName();
                $extension      = $file->getClientOriginalExtension();
                $nombreUnico    = uniqid() . '_' . time() . '.' . $extension;

                $path = "process_juridic/{$processId}/{$tipo}";
                $rutaGuardada = $file->storeAs($path, $nombreUnico, 'public');
                $urlCompleta  = $this->getStorageUrl($rutaGuardada);

                DB::table($tabla)->insert([
                    $colProceso       => $processId,
                    'evidencia_url'   => $urlCompleta,
                    'nombre_original' => $nombreOriginal,
                    'tipo_archivo'    => $file->getMimeType(),
                    'tamano'          => $file->getSize(),
                    'fecha_subida'    => now(),
                ]);

                Log::info("Archivo guardado: {$rutaGuardada} | URL: {$urlCompleta}");
            }
        }
    }

    /**
     * Guarda los archivos de evidencias en disco y registra en BD (para creación inicial).
     * Soporta archivos individuales o múltiples (arrays).
     */
    private function saveEvidenciasFiles($processId, $request)
    {
        $tablas = $this->getTablasEvidencias();
        $colProceso = $this->getColumnaProcesoId();

        foreach ($tablas as $key => $tabla) {
            $campo = "evidencias_{$key}";

            if ($request->hasFile($campo)) {
                $files = $request->file($campo);
                if (!is_array($files)) {
                    $files = [$files];
                }

                Log::info("Guardando {$key} evidencias para proceso {$processId}. Cantidad: " . count($files));

                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $nombreOriginal = $file->getClientOriginalName();
                        $extension      = $file->getClientOriginalExtension();
                        $nombreUnico    = uniqid() . '_' . time() . '.' . $extension;

                        $path        = "process_juridic/{$processId}/{$key}";
                        $rutaGuardada = $file->storeAs($path, $nombreUnico, 'public');
                        $urlCompleta   = $this->getStorageUrl($rutaGuardada);

                        DB::table($tabla)->insert([
                            $colProceso       => $processId,
                            'evidencia_url'   => $urlCompleta,
                            'nombre_original' => $nombreOriginal,
                            'tipo_archivo'    => $file->getMimeType(),
                            'tamano'          => $file->getSize(),
                            'fecha_subida'    => now(),
                        ]);

                        Log::info("Archivo en disco: {$rutaGuardada} | URL en BD: {$urlCompleta}");
                    } else {
                        Log::warning("Archivo inválido en {$campo}");
                    }
                }
            }
        }
    }

    /**
     * Elimina los archivos físicos de todas las evidencias de un proceso (usado en destroy).
     * Solo elimina archivos locales (que empiecen con la URL de storage).
     */
    private function deleteEvidenciasFiles($processId)
    {
        $tablas = $this->getTablasEvidencias();
        $colProceso = $this->getColumnaProcesoId();

        foreach ($tablas as $tabla) {
            $evidencias = DB::table($tabla)
                ->where($colProceso, $processId)
                ->get();

            foreach ($evidencias as $evidencia) {
                $this->deleteFileIfLocal($evidencia->evidencia_url);
            }
        }
    }

    /**
     * Elimina los registros de evidencias de la base de datos (todos los tipos).
     */
    private function deleteEvidencias($processId)
    {
        $tablas = $this->getTablasEvidencias();
        $colProceso = $this->getColumnaProcesoId();

        foreach ($tablas as $tabla) {
            DB::table($tabla)
                ->where($colProceso, $processId)
                ->delete();
        }
    }

    /**
     * Elimina un archivo físico solo si la URL corresponde al almacenamiento local y el archivo existe.
     */
    private function deleteFileIfLocal($url)
    {
        $baseUrl = env('APP_URL', 'http://localhost:8000') . '/storage/';
        if (strpos($url, $baseUrl) === 0) {
            $ruta = $this->urlToPath($url);
            if (!empty($ruta) && Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
                Log::info("Archivo eliminado: {$ruta}");
            } else {
                Log::warning("Archivo no encontrado, no se elimina: {$ruta}");
            }
        } else {
            Log::info("No se elimina archivo externo: {$url}");
        }
    }

    /**
     * Convierte una URL de evidencia a ruta de almacenamiento.
     */
    private function urlToPath($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $baseUrl = env('APP_URL', 'http://localhost:8000');
        $storageBase = $baseUrl . '/storage/';

        $path = str_replace($storageBase, '', $url);

        if ($path === $url) {
            $parsed = parse_url($url);
            $path = isset($parsed['path']) ? $parsed['path'] : '';
            $path = str_replace('/storage/', '', $path);
        }

        return $path;
    }

    /**
     * Obtiene la URL pública de un archivo almacenado.
     */
    private function getStorageUrl($path)
    {
        $baseUrl = env('APP_URL', 'http://localhost:8000');
        $url = $baseUrl . '/storage/' . $path;
        Log::info("getStorageUrl input: {$path} | output: {$url}");
        return $url;
    }
}
