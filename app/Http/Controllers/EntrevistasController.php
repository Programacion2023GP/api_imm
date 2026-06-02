<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Entrevista;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntrevistasController extends Controller
{
    protected $model = Entrevista::class;
    private $catalogos = [
        'espacios_digitales' => 'espacio_digital',
        'espacios_particulares' => 'espacio_particular',
        'espacios_publicos' => 'espacio_publico',
        'transportes_foraneos' => 'transporte_foraneo',
        'transportes_privados' => 'transporte_privado',
        'transportes_urbanos' => 'transporte_urbano',
        'tipos_violencia' => 'tipos_violencia',
        'ambitos_violencia' => 'ambitos_violencia',
        'efectos_fisicos' => 'efectos_fisicos',
        'consecuencias_sexuales' => 'consecuencias_sexuales',
        'efectos_psicologicos' => 'efectos_psicologicos',
        'efectos_economicos' => 'efectos_economicos_patrimoniales',
        'agentes_lesion' => 'agente_lesion', // ✅ corregido: 'agente_lesion' (singular)
        'areas_anatomicas' => 'area_anatomica_lesionada', // ✅ corregido
        'orientaciones_sexuales' => 'orientacion_sexual', // ✅ corregido (singular)
        'identidades_genero' => 'identidad_genero', // ✅ corregido (singular)
        'estados_civiles' => 'estado_civil', // ✅ corregido (singular)
        'grados_estudios' => 'ultimo_grado_estudios', // ✅ corregido
        'ingresos_promedio' => 'ingresos_promedio_mensuales', // ✅ corregido
        'actividades' => 'actividad_principal', // ✅ corregido
        'servicios_medicos' => 'servicio_medico', // ✅ corregido (singular)
        'discapacidades' => 'discapacidades',
        'vinculos' => 'relacion', // ✅ corregido: la tabla es 'relacion'
        'ocupaciones' => 'ocupaciones',
        'armas' => 'armas',
        'sustancias' => 'sustancias',
        'servicios_social' => 'servicios_trabajo_social', // ✅ corregido
        'servicios_juridicos' => 'servicios_juridicos',
        'servicios_psicologicos' => 'servicios_psicologicos',
        'dependencias' => 'dependencias',
        'canalizaciones' => 'canalizacion', // ✅ corregido (singular)
    ];
    /**
     * UPSERT: Crear o actualizar entrevista
     */
    public function createOrUpdate(Request $request)
    {
        try {
            DB::beginTransaction();

            // Preparar los datos antes de insertar/actualizar
            $data = $request->except([
                'id',
                'dependientes',
                'redapoyo',
                'id_espacio_digital',
                'id_espacio_particular',
                'id_espacio_publico',
                'id_transporte_foraneo',
                'id_transporte_urbano',
                'id_transporte_privado',
                'id_tipos_violencia',
                'id_ambitos_violencia',
                'id_efectos_fisicos',
                'id_consecuencias_sexuales',
                'id_efectos_psicologicos',
                'id_efectos_economicos_patrimoniales',
                'id_agente_lesion',
                'id_aerea_anatomica_lesionada',
                'id_drogas_agresor',
                'id_servicios_trabajo_social',
                'id_servicios_juridicos',
                'id_servicios_psicologicos'
            ]);
        

            // 🔧 FORMATO DE FECHAS Y HORAS
            if (isset($data['fecha_hecho']) && !empty($data['fecha_hecho'])) {
                $data['fecha_hecho'] = Carbon::parse($data['fecha_hecho'])->format('Y-m-d');
            }

            if (isset($data['hora_hecho']) && !empty($data['hora_hecho'])) {
                // Si viene como fecha completa, extraer solo la hora
                if (strlen($data['hora_hecho']) > 8) {
                    $data['hora_hecho'] = Carbon::parse($data['hora_hecho'])->format('H:i:s');
                } elseif (!str_contains($data['hora_hecho'], ':')) {
                    // Si viene como número o formato inválido, usar default
                    $data['hora_hecho'] = '00:00:00';
                }
            }

            if (isset($data['fecha_nacimiento']) && !empty($data['fecha_nacimiento'])) {
                $data['fecha_nacimiento'] = Carbon::parse($data['fecha_nacimiento'])->format('Y-m-d');
            }

            if (isset($data['fecha_canalizacion']) && !empty($data['fecha_canalizacion'])) {
                $data['fecha_canalizacion'] = Carbon::parse($data['fecha_canalizacion'])->format('Y-m-d H:i:s');
            }

            // Convertir booleanos a 0/1 (MySQL)
            $booleanFields = [
                'ocurrio_domicilio_victima',
                'ocurrio_extranjero',
                'dia_festivo',
                'conoce_autoridad_asunto',
                'canalizado_cabi',
                'victima_delicuencia_organizada',
                'relacion_denuncia',
                'relacionado_orientacion_indetidad_genero',
                'vive_extrajero',
                'realiza_mas_actividades',
                'migrante',
                'pertenece_pueblo_indigena',
                'tiene_discapacidad',
                'discapacidad_causada_violencia',
                'enfermedad_cronica_degenerativa',
                'trastorno_neurologico_mental',
                'tratado_medicamente_actualmente',
                'embarazo',
                'tiene_dependientes',
                'vive_extranjero',
                'vive_situacion_calle',
                'tiene_adiccion',
                'conoce_agresor',
                'vive_domicilio_victima',
                'acceso_armas_agresor',
                'acceso_drogas_agresor'
            ];

            foreach ($booleanFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                }
            }

            // Buscar si existe
            $entrevista = Entrevista::find($request->id);

            if ($entrevista) {
                // ACTUALIZAR
                $entrevista->update($data);

                // Eliminar relaciones viejas
                $this->deleteRelations($entrevista->id);
            } else {
                // INSERTAR
                $data['id_user_created'] = Auth::user()->id;

                $entrevista = Entrevista::create($data);
            }

            // Insertar dependientes (corregir tipos booleanos)
            if ($request->has('dependientes') && is_array($request->dependientes)) {
                foreach ($request->dependientes as $dep) {
                    DB::table('dependientes')->insert([
                        'entrevista_id' => $entrevista->id,
                        'nombre' => $dep['nombre'] ?? null,
                        'apellido_paterno' => $dep['apellido_paterno'] ?? null,
                        'apellido_materno' => $dep['apellido_materno'] ?? null,
                        'id_vinculo' => $dep['id_vinculo'] ?? null,
                        'esta_riesgo' => isset($dep['esta_riesgo']) ? filter_var($dep['esta_riesgo'], FILTER_VALIDATE_BOOLEAN) : false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Insertar red de apoyo
            if ($request->has('redapoyo') && is_array($request->redapoyo)) {
                foreach ($request->redapoyo as $red) {
                    DB::table('red_apoyo')->insert([
                        'entrevista_id' => $entrevista->id,
                        'nombre' => $red['nombre'] ?? null,
                        'apellido_paterno' => $red['apellido_paterno'] ?? null,
                        'apellido_materno' => $red['apellido_materno'] ?? null,
                        'id_vinculo' => $red['id_vinculo'] ?? null,
                        'cuenta_apoyo' => isset($red['cuenta_apoyo']) ? filter_var($red['cuenta_apoyo'], FILTER_VALIDATE_BOOLEAN) : false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Insertar relaciones muchos a muchos
            $this->insertManyToMany('entrevista_espacios_digitales', $entrevista->id, 'id_espacio_digital', $request->id_espacio_digital ?? []);
            $this->insertManyToMany('entrevista_espacios_particulares', $entrevista->id, 'id_espacio_particular', $request->id_espacio_particular ?? []);
            $this->insertManyToMany('entrevista_espacios_publicos', $entrevista->id, 'id_espacio_publico', $request->id_espacio_publico ?? []);
            $this->insertManyToMany('entrevista_transporte_foraneo', $entrevista->id, 'id_transporte_foraneo', $request->id_transporte_foraneo ?? []);
            $this->insertManyToMany('entrevista_transporte_urbano', $entrevista->id, 'id_transporte_urbano', $request->id_transporte_urbano ?? []);
            $this->insertManyToMany('entrevista_transporte_privado', $entrevista->id, 'id_transporte_privado', $request->id_transporte_privado ?? []);
            $this->insertManyToMany('entrevista_tipos_violencia', $entrevista->id, 'id_tipo_violencia', $request->id_tipos_violencia ?? []);
            $this->insertManyToMany('entrevista_ambitos_violencia', $entrevista->id, 'id_ambito_violencia', $request->id_ambitos_violencia ?? []);
            $this->insertManyToMany('entrevista_efectos_fisicos', $entrevista->id, 'id_efecto_fisico', $request->id_efectos_fisicos ?? []);
            $this->insertManyToMany('entrevista_consecuencias_sexuales', $entrevista->id, 'id_consecuencia_sexual', $request->id_consecuencias_sexuales ?? []);
            $this->insertManyToMany('entrevista_efectos_psicologicos', $entrevista->id, 'id_efecto_psicologico', $request->id_efectos_psicologicos ?? []);
            $this->insertManyToMany('entrevista_efectos_economicos', $entrevista->id, 'id_efecto_economico', $request->id_efectos_economicos_patrimoniales ?? []);
            $this->insertManyToMany('entrevista_agente_lesion', $entrevista->id, 'id_agente_lesion', $request->id_agente_lesion ?? []);
            $this->insertManyToMany('entrevista_area_anatomica', $entrevista->id, 'id_area_anatomica', $request->id_aerea_anatomica_lesionada ?? []);
            $this->insertManyToMany('entrevista_drogas_agresor', $entrevista->id, 'id_droga', $request->id_drogas_agresor ?? []);
            $this->insertManyToMany('entrevista_servicios_trabajo_social', $entrevista->id, 'id_servicio', $request->id_servicios_trabajo_social ?? []);
            $this->insertManyToMany('entrevista_servicios_juridicos', $entrevista->id, 'id_servicio', $request->id_servicios_juridicos ?? []);
            $this->insertManyToMany('entrevista_servicios_psicologicos', $entrevista->id, 'id_servicio', $request->id_servicios_psicologicos ?? []);

            DB::commit();

            return ApiResponse::success(
                $entrevista,
                $entrevista->wasRecentlyCreated ? 'Entrevista creada exitosamente' : 'Entrevista actualizada exitosamente'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info($e->getMessage());
            return ApiResponse::error('Ocurrio un error', 500);
        }
    }

    /**
     * GET: Obtener entrevista completa con INNER JOINS
     */

    public function all()
    {
        try {
            // Para roles 1 y 2 (admin/superadmin) -> ven TODAS las entrevistas
            // Para otros roles -> solo ven las que ellos crearon
            if (in_array(Auth::user()->id_rol, [1, 2])) {
                $entrevistas = Entrevista::all();
            } else {
                $entrevistas = Entrevista::where('id_user_created', Auth::id())->get();
            }

            if ($entrevistas->isEmpty()) {
                return ApiResponse::success(
                    [],
                    'No hay entrevistas registradas'
                );
            }

            // Preparar el resultado
            $result = [];

            foreach ($entrevistas as $entrevista) {
                $item = $entrevista->toArray();
                $id = $entrevista->id;

                // Cargar relaciones uno a muchos (listas de objetos)
                $item['dependientes'] = DB::table('dependientes')
                    ->where('entrevista_id', $id)
                    ->get();

                $item['redapoyo'] = DB::table('red_apoyo')
                    ->where('entrevista_id', $id)
                    ->get();

                // Cargar relaciones muchos a muchos (arrays de IDs)
                $item['id_espacio_digital'] = DB::table('entrevista_espacios_digitales')
                    ->where('entrevista_id', $id)
                    ->pluck('id_espacio_digital')
                    ->toArray();

                $item['id_espacio_particular'] = DB::table('entrevista_espacios_particulares')
                    ->where('entrevista_id', $id)
                    ->pluck('id_espacio_particular')
                    ->toArray();

                $item['id_espacio_publico'] = DB::table('entrevista_espacios_publicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_espacio_publico')
                    ->toArray();

                $item['id_transporte_foraneo'] = DB::table('entrevista_transporte_foraneo')
                    ->where('entrevista_id', $id)
                    ->pluck('id_transporte_foraneo')
                    ->toArray();

                $item['id_transporte_urbano'] = DB::table('entrevista_transporte_urbano')
                    ->where('entrevista_id', $id)
                    ->pluck('id_transporte_urbano')
                    ->toArray();

                $item['id_transporte_privado'] = DB::table('entrevista_transporte_privado')
                    ->where('entrevista_id', $id)
                    ->pluck('id_transporte_privado')
                    ->toArray();

                $item['id_tipos_violencia'] = DB::table('entrevista_tipos_violencia')
                    ->where('entrevista_id', $id)
                    ->pluck('id_tipo_violencia')
                    ->toArray();

                $item['id_ambitos_violencia'] = DB::table('entrevista_ambitos_violencia')
                    ->where('entrevista_id', $id)
                    ->pluck('id_ambito_violencia')
                    ->toArray();

                $item['id_efectos_fisicos'] = DB::table('entrevista_efectos_fisicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_efecto_fisico')
                    ->toArray();

                $item['id_consecuencias_sexuales'] = DB::table('entrevista_consecuencias_sexuales')
                    ->where('entrevista_id', $id)
                    ->pluck('id_consecuencia_sexual')
                    ->toArray();

                $item['id_efectos_psicologicos'] = DB::table('entrevista_efectos_psicologicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_efecto_psicologico')
                    ->toArray();

                $item['id_efectos_economicos_patrimoniales'] = DB::table('entrevista_efectos_economicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_efecto_economico')
                    ->toArray();

                $item['id_agente_lesion'] = DB::table('entrevista_agente_lesion')
                    ->where('entrevista_id', $id)
                    ->pluck('id_agente_lesion')
                    ->toArray();

                $item['id_aerea_anatomica_lesionada'] = DB::table('entrevista_area_anatomica')
                    ->where('entrevista_id', $id)
                    ->pluck('id_area_anatomica')
                    ->toArray();

                $item['id_drogas_agresor'] = DB::table('entrevista_drogas_agresor')
                    ->where('entrevista_id', $id)
                    ->pluck('id_droga')
                    ->toArray();

                $item['id_servicios_trabajo_social'] = DB::table('entrevista_servicios_trabajo_social')
                    ->where('entrevista_id', $id)
                    ->pluck('id_servicio')
                    ->toArray();

                $item['id_servicios_juridicos'] = DB::table('entrevista_servicios_juridicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_servicio')
                    ->toArray();

                $item['id_servicios_psicologicos'] = DB::table('entrevista_servicios_psicologicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_servicio')
                    ->toArray();

                $result[] = $item;
            }

            return ApiResponse::success(
                $result,
                'Datos de entrevistas obtenidos correctamente'
            );
        } catch (\Exception $e) {
            Log::error('Error en all(): ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }


    private function loadCatalogs()
    {
        $catalogos = [];

        foreach ($this->catalogos as $key => $table) {
            $catalogos[$key] = DB::table($table)
                ->select('id', 'nombre')
                ->get()
                ->keyBy('id')
                ->map(function ($item) {
                    return $item->nombre;
                })
                ->toArray();
        }

        return $catalogos;
    }

    /**
     * Resuelve campos simples (que no son múltiples)
     */
    private function resolveSimpleFields($item, $catalogosData)
    {
        $simpleFields = [
            'id_orientacion_sexual' => 'orientaciones_sexuales',
            'id_identidad_genero' => 'identidades_genero',
            'id_estado_civil' => 'estados_civiles',
            'id_ultimo_grado_estudios' => 'grados_estudios',
            'id_ingreso_promedio_mensual' => 'ingresos_promedio',
            'id_actividad' => 'actividades',
            'id_servicio_medico' => 'servicios_medicos',
            'id_discapacidad' => 'discapacidades',
            'id_vinculo_agresor' => 'vinculos',
            'id_identidad_genero_agresor' => 'identidades_genero',
            'id_orientacion_sexual_agresor' => 'orientaciones_sexuales',
            'id_ultimo_grado_estudios_agresor' => 'grados_estudios',
            'id_ingreso_promedio_mensual_agresor' => 'ingresos_promedio',
            'id_ocupacion_agresor' => 'ocupaciones',
            'id_armas_agresor' => 'armas',
            'id_dependencia' => 'dependencias',
            'id_canalizacion' => 'canalizaciones',
        ];

        foreach ($simpleFields as $field => $catalogKey) {
            if (isset($item[$field]) && $item[$field]) {
                $nombre = $catalogosData[$catalogKey][$item[$field]] ?? null;
                if ($nombre) {
                    $item[$field . '_nombre'] = $nombre;
                }
            }
        }

        return $item;
    }

    /**
     * Carga y resuelve relaciones muchos a muchos
     */
    private function loadManyToManyRelations($entrevistaId, $item, $catalogosData)
    {
        $relations = [
            'id_espacio_digital' => [
                'table' => 'entrevista_espacios_digitales',
                'foreign_key' => 'id_espacio_digital',
                'catalog' => 'espacios_digitales'
            ],
            'id_espacio_particular' => [
                'table' => 'entrevista_espacios_particulares',
                'foreign_key' => 'id_espacio_particular',
                'catalog' => 'espacios_particulares'
            ],
            'id_espacio_publico' => [
                'table' => 'entrevista_espacios_publicos',
                'foreign_key' => 'id_espacio_publico',
                'catalog' => 'espacios_publicos'
            ],
            'id_transporte_foraneo' => [
                'table' => 'entrevista_transporte_foraneo',
                'foreign_key' => 'id_transporte_foraneo',
                'catalog' => 'transportes_foraneos'
            ],
            'id_transporte_privado' => [
                'table' => 'entrevista_transporte_privado',
                'foreign_key' => 'id_transporte_privado',
                'catalog' => 'transportes_privados'
            ],
            'id_transporte_urbano' => [
                'table' => 'entrevista_transporte_urbano',
                'foreign_key' => 'id_transporte_urbano',
                'catalog' => 'transportes_urbanos'
            ],
            'id_tipos_violencia' => [
                'table' => 'entrevista_tipos_violencia',
                'foreign_key' => 'id_tipo_violencia',
                'catalog' => 'tipos_violencia'
            ],
            'id_ambitos_violencia' => [
                'table' => 'entrevista_ambitos_violencia',
                'foreign_key' => 'id_ambito_violencia',
                'catalog' => 'ambitos_violencia'
            ],
            'id_efectos_fisicos' => [
                'table' => 'entrevista_efectos_fisicos',
                'foreign_key' => 'id_efecto_fisico',
                'catalog' => 'efectos_fisicos'
            ],
            'id_consecuencias_sexuales' => [
                'table' => 'entrevista_consecuencias_sexuales',
                'foreign_key' => 'id_consecuencia_sexual',
                'catalog' => 'consecuencias_sexuales'
            ],
            'id_efectos_psicologicos' => [
                'table' => 'entrevista_efectos_psicologicos',
                'foreign_key' => 'id_efecto_psicologico',
                'catalog' => 'efectos_psicologicos'
            ],
            'id_efectos_economicos_patrimoniales' => [
                'table' => 'entrevista_efectos_economicos',
                'foreign_key' => 'id_efecto_economico',
                'catalog' => 'efectos_economicos'
            ],
            'id_agente_lesion' => [
                'table' => 'entrevista_agente_lesion',
                'foreign_key' => 'id_agente_lesion',
                'catalog' => 'agentes_lesion'
            ],
            'id_aerea_anatomica_lesionada' => [
                'table' => 'entrevista_area_anatomica',
                'foreign_key' => 'id_area_anatomica',
                'catalog' => 'areas_anatomicas'
            ],
            'id_drogas_agresor' => [
                'table' => 'entrevista_drogas_agresor',
                'foreign_key' => 'id_droga',
                'catalog' => 'sustancias'
            ],
            'id_servicios_trabajo_social' => [
                'table' => 'entrevista_servicios_trabajo_social',
                'foreign_key' => 'id_servicio',
                'catalog' => 'servicios_social'
            ],
            'id_servicios_juridicos' => [
                'table' => 'entrevista_servicios_juridicos',
                'foreign_key' => 'id_servicio',
                'catalog' => 'servicios_juridicos'
            ],
            'id_servicios_psicologicos' => [
                'table' => 'entrevista_servicios_psicologicos',
                'foreign_key' => 'id_servicio',
                'catalog' => 'servicios_psicologicos'
            ],
        ];

        foreach ($relations as $field => $config) {
            // Obtener IDs
            $ids = DB::table($config['table'])
                ->where('entrevista_id', $entrevistaId)
                ->pluck($config['foreign_key'])
                ->toArray();

            $item[$field] = $ids;

            // Resolver nombres
            $nombres = [];
            foreach ($ids as $id) {
                if (isset($catalogosData[$config['catalog']][$id])) {
                    $nombres[] = $catalogosData[$config['catalog']][$id];
                }
            }
            $item[$field . '_nombres'] = implode(', ', $nombres);
        }

        return $item;
    }

    /**
     * Carga y resuelve dependientes y red de apoyo
     */
    private function loadDependientesAndRedApoyo($entrevistaId, $item, $catalogosData)
    {
        // Dependientes
        $dependientes = DB::table('dependientes')
            ->where('entrevista_id', $entrevistaId)
            ->get()
            ->toArray();

        foreach ($dependientes as &$dep) {
            $dep->vinculo_nombre = $catalogosData['vinculos'][$dep->id_vinculo] ?? '';
            $dep->esta_riesgo_texto = $dep->esta_riesgo ? 'Sí' : 'No';
        }
        $item['dependientes'] = $dependientes;

        // Red de apoyo
        $redapoyo = DB::table('red_apoyo')
            ->where('entrevista_id', $entrevistaId)
            ->get()
            ->toArray();

        foreach ($redapoyo as &$red) {
            $red->vinculo_nombre = $catalogosData['vinculos'][$red->id_vinculo] ?? '';
            $red->cuenta_apoyo_texto = $red->cuenta_apoyo ? 'Sí' : 'No';
        }
        $item['redapoyo'] = $redapoyo;

        return $item;
    }

    /**
     * Resuelve nombres de colonias (si aplica)
     */
    private function resolveColonias($item)
    {
        // Si tienes una tabla de colonias, resuélvela aquí
        if (isset($item['colonia']) && is_numeric($item['colonia'])) {
            $colonia = DB::table('cat_colonias')
                ->where('id', $item['colonia'])
                ->first();
            $item['colonia_nombre'] = $colonia->nombre ?? $item['colonia'];
        } else {
            $item['colonia_nombre'] = $item['colonia'] ?? '';
        }

        if (isset($item['colonia_agresor']) && is_numeric($item['colonia_agresor'])) {
            $colonia = DB::table('cat_colonias')
                ->where('id', $item['colonia_agresor'])
                ->first();
            $item['colonia_agresor_nombre'] = $colonia->nombre ?? $item['colonia_agresor'];
        } else {
            $item['colonia_agresor_nombre'] = $item['colonia_agresor'] ?? '';
        }

        return $item;
    }
    public function show($id)
    {
        try {
            $entrevista = Entrevista::where('id', $id)->first();

            if (!$entrevista) {
                return ApiResponse::error('Entrevista no encontrada', 404);
            }

            $item = $entrevista->toArray();
            $catalogosData = $this->loadCatalogs();

            $item = $this->resolveSimpleFields($item, $catalogosData);
            $item = $this->loadManyToManyRelations($id, $item, $catalogosData);
            $item = $this->loadDependientesAndRedApoyo($id, $item, $catalogosData);
            $item = $this->resolveColonias($item);

            return ApiResponse::success($item, 'Entrevista obtenida correctamente');
        } catch (\Exception $e) {
            Log::error('Error en show(): ' . $e->getMessage());
            return  ApiResponse::error('Ocurrió un error', 500);
        }
    }
    public function alldata()
    {
        try {
            // Para roles 1 y 2 (admin/superadmin) -> ven TODAS las entrevistas
            // Para otros roles -> solo ven las que ellos crearon
                $entrevistas = Entrevista::all();
           

            if ($entrevistas->isEmpty()) {
                return ApiResponse::success(
                    [],
                    'No hay entrevistas registradas'
                );
            }

            // Preparar el resultado
            $result = [];

            foreach ($entrevistas as $entrevista) {
                $item = $entrevista->toArray();
                $id = $entrevista->id;

                // Cargar relaciones uno a muchos (listas de objetos)
                $item['dependientes'] = DB::table('dependientes')
                    ->where('entrevista_id', $id)
                    ->get();

                $item['redapoyo'] = DB::table('red_apoyo')
                    ->where('entrevista_id', $id)
                    ->get();

                // Cargar relaciones muchos a muchos (arrays de IDs)
                $item['id_espacio_digital'] = DB::table('entrevista_espacios_digitales')
                    ->where('entrevista_id', $id)
                    ->pluck('id_espacio_digital')
                    ->toArray();

                $item['id_espacio_particular'] = DB::table('entrevista_espacios_particulares')
                    ->where('entrevista_id', $id)
                    ->pluck('id_espacio_particular')
                    ->toArray();

                $item['id_espacio_publico'] = DB::table('entrevista_espacios_publicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_espacio_publico')
                    ->toArray();

                $item['id_transporte_foraneo'] = DB::table('entrevista_transporte_foraneo')
                    ->where('entrevista_id', $id)
                    ->pluck('id_transporte_foraneo')
                    ->toArray();

                $item['id_transporte_urbano'] = DB::table('entrevista_transporte_urbano')
                    ->where('entrevista_id', $id)
                    ->pluck('id_transporte_urbano')
                    ->toArray();

                $item['id_transporte_privado'] = DB::table('entrevista_transporte_privado')
                    ->where('entrevista_id', $id)
                    ->pluck('id_transporte_privado')
                    ->toArray();

                $item['id_tipos_violencia'] = DB::table('entrevista_tipos_violencia')
                    ->where('entrevista_id', $id)
                    ->pluck('id_tipo_violencia')
                    ->toArray();

                $item['id_ambitos_violencia'] = DB::table('entrevista_ambitos_violencia')
                    ->where('entrevista_id', $id)
                    ->pluck('id_ambito_violencia')
                    ->toArray();

                $item['id_efectos_fisicos'] = DB::table('entrevista_efectos_fisicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_efecto_fisico')
                    ->toArray();

                $item['id_consecuencias_sexuales'] = DB::table('entrevista_consecuencias_sexuales')
                    ->where('entrevista_id', $id)
                    ->pluck('id_consecuencia_sexual')
                    ->toArray();

                $item['id_efectos_psicologicos'] = DB::table('entrevista_efectos_psicologicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_efecto_psicologico')
                    ->toArray();

                $item['id_efectos_economicos_patrimoniales'] = DB::table('entrevista_efectos_economicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_efecto_economico')
                    ->toArray();

                $item['id_agente_lesion'] = DB::table('entrevista_agente_lesion')
                    ->where('entrevista_id', $id)
                    ->pluck('id_agente_lesion')
                    ->toArray();

                $item['id_aerea_anatomica_lesionada'] = DB::table('entrevista_area_anatomica')
                    ->where('entrevista_id', $id)
                    ->pluck('id_area_anatomica')
                    ->toArray();

                $item['id_drogas_agresor'] = DB::table('entrevista_drogas_agresor')
                    ->where('entrevista_id', $id)
                    ->pluck('id_droga')
                    ->toArray();

                $item['id_servicios_trabajo_social'] = DB::table('entrevista_servicios_trabajo_social')
                    ->where('entrevista_id', $id)
                    ->pluck('id_servicio')
                    ->toArray();

                $item['id_servicios_juridicos'] = DB::table('entrevista_servicios_juridicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_servicio')
                    ->toArray();

                $item['id_servicios_psicologicos'] = DB::table('entrevista_servicios_psicologicos')
                    ->where('entrevista_id', $id)
                    ->pluck('id_servicio')
                    ->toArray();

                $result[] = $item;
            }

            return ApiResponse::success(
                $result,
                'Datos de entrevistas obtenidos correctamente'
            );
        } catch (\Exception $e) {
            Log::error('Error en all(): ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error', 500);
        }
    }
    /**
     * Helper: Eliminar relaciones
     */
    private function deleteRelations($entrevistaId)
    {
        DB::table('dependientes')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('red_apoyo')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_espacios_digitales')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_espacios_particulares')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_espacios_publicos')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_transporte_foraneo')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_transporte_urbano')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_transporte_privado')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_tipos_violencia')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_ambitos_violencia')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_efectos_fisicos')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_consecuencias_sexuales')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_efectos_psicologicos')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_efectos_economicos')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_agente_lesion')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_area_anatomica')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_drogas_agresor')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_servicios_trabajo_social')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_servicios_juridicos')->where('entrevista_id', $entrevistaId)->delete();
        DB::table('entrevista_servicios_psicologicos')->where('entrevista_id', $entrevistaId)->delete();
    }

    /**
     * Helper: Insertar relaciones muchos a muchos
     */
    private function insertManyToMany($table, $entrevistaId, $column, $values)
    {
        if (empty($values) || !is_array($values)) {
            return;
        }

        $inserts = [];
        foreach ($values as $value) {
            $inserts[] = [
                'entrevista_id' => $entrevistaId,
                $column => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($inserts)) {
            DB::table($table)->insert($inserts);
        }
    }

    public function lobyPsicologico(){
        try {
            $query = Entrevista::select(
                'entrevistas.id',
                'entrevistas.curp',
                DB::raw("GROUP_CONCAT(sp.nombre SEPARATOR ', ') as servicios_psicologicos")
            )
                ->join('entrevista_servicios_psicologicos as esp', 'esp.entrevista_id', '=', 'entrevistas.id')
                ->join('servicios_psicologicos as sp', 'sp.id', '=', 'esp.id_servicio')
                ->groupBy('entrevistas.id', 'entrevistas.curp')->get();
            return ApiResponse::success(
                $query,
                'data de entrevistas'
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return ApiResponse::error('Ocurrio un error', 500);
        }
    }
}
