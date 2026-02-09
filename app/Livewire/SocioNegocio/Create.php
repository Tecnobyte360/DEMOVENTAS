<?php

namespace App\Livewire\SocioNegocio;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

use App\Models\Municipio;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\SocioNegocio\SocioDireccion;
use App\Models\CondicionPago\CondicionPago;

use Masmerise\Toaster\PendingToast;

class Create extends Component
{
    /* ============================================================
     |  CAMPOS BASE (OBLIGATORIOS HASTA DIRECCIÓN)
     ============================================================ */
    public string $razon_social = '';
    public ?string $nit = null;
    public string $Tipo = 'C'; // C=Cliente, P=Proveedor

    public ?string $telefono_fijo = null;
    public ?string $telefono_movil = null;
    public ?string $correo = null;
    public ?string $direccion = null;

    public ?string $municipio_barrio = null;
    public ?float $saldo_pendiente = 0;

    /* ============================================================
     |  CONDICIÓN DE PAGO (OBLIGATORIA)
     ============================================================ */
    public ?int $condicion_pago_id = null;

    /* ============================================================
     |  CAMPOS FISCALES
     ============================================================ */
    public string $tipo_persona = 'N';              // N=Natural, J=Jurídica
    public string $regimen_iva  = 'no_responsable'; // responsable | no_responsable
    public bool $regimen_simple = false;

    public ?int $municipio_id = null;               // municipio principal del socio
    public ?string $actividad_economica = null;
    public ?string $direccion_medios_magneticos = null;

    /* ============================================================
     |  CATÁLOGOS
     ============================================================ */
    public array $municipios = [];

    /* ============================================================
     |  DIRECCIONES (REPEATER)
     ============================================================ */
    public array $direcciones = [];

    public function mount(): void
    {
        $this->cargarMunicipios();

        $this->direcciones = [[
            'id'           => null,
            'nombre'       => null,
            'direccion'    => null,
            'referencia'   => null,
            'municipio_id' => null,
            'es_principal' => true,
        ]];
    }

    private function cargarMunicipios(): void
    {
        $modelClass = class_exists(Municipio::class) ? Municipio::class : null;

        $q = $modelClass
            ? $modelClass::query()->orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        $this->municipios = $q->map(fn ($m) => ['id' => $m->id, 'nombre' => $m->nombre])->toArray();

        if (empty($this->municipios)) {
            $this->municipios = [
                ['id' => 1, 'nombre' => 'Medellín'],
                ['id' => 2, 'nombre' => 'Itagüí'],
                ['id' => 3, 'nombre' => 'La Estrella'],
            ];
        }
    }

    /* ============================================================
     |  REPEATER HANDLERS
     ============================================================ */
    public function addDireccion(): void
    {
        $this->direcciones[] = [
            'id'           => null,
            'nombre'       => null,
            'direccion'    => null,
            'referencia'   => null,
            'municipio_id' => $this->municipio_id,
            'es_principal' => false,
        ];
    }

    public function removeDireccion(int $index): void
    {
        if (!isset($this->direcciones[$index])) return;

        $removedWasPrincipal = (bool) ($this->direcciones[$index]['es_principal'] ?? false);

        unset($this->direcciones[$index]);
        $this->direcciones = array_values($this->direcciones);

        if ($removedWasPrincipal && count($this->direcciones) > 0) {
            foreach ($this->direcciones as $i => $d) {
                $this->direcciones[$i]['es_principal'] = false;
            }
            $this->direcciones[0]['es_principal'] = true;
        }
    }

    public function setPrincipal(int $index): void
    {
        foreach ($this->direcciones as $i => $d) {
            $this->direcciones[$i]['es_principal'] = ($i === $index);
        }
    }

    /* ============================================================
     |  VALIDACIÓN
     ============================================================ */
    protected function rules(): array
    {
        return [
            'razon_social' => ['required', 'string', 'max:190'],
            'nit'          => ['required', 'string', 'regex:/^\d{6,20}$/', 'unique:socio_negocios,nit'],
            'Tipo'         => ['required', 'in:C,P'],

            // obligatorios hasta dirección
            'telefono_fijo'  => ['required', 'string', 'regex:/^\d{7,15}$/'],
            'telefono_movil' => ['required', 'string', 'regex:/^\d{7,15}$/'],
            'correo'         => ['required', 'email', 'max:190'],
            'direccion'      => ['required', 'string', 'max:190'],

            // ✅ condición de pago OBLIGATORIA
            'condicion_pago_id' => ['required', 'integer', 'exists:condicion_pagos,id'],

            // demás
            'municipio_barrio' => ['nullable', 'string', 'max:190'],
            'saldo_pendiente'  => ['nullable', 'numeric', 'min:0'],

            // fiscales
            'tipo_persona' => ['required', 'in:N,J'],
            'regimen_iva'  => ['required', 'in:responsable,no_responsable'],
            'regimen_simple' => ['boolean'],
            'municipio_id' => ['nullable', 'integer'],
            'actividad_economica' => ['nullable', 'string', 'max:20'],
            'direccion_medios_magneticos' => ['nullable', 'string', 'max:190'],

            // repeater
            'direcciones' => ['array'],
            'direcciones.*.nombre'       => ['nullable', 'string', 'max:120'],
            'direcciones.*.direccion'    => ['nullable', 'string', 'max:190'],
            'direcciones.*.referencia'   => ['nullable', 'string', 'max:190'],
            'direcciones.*.municipio_id' => ['nullable', 'integer'],
            'direcciones.*.es_principal' => ['boolean'],
        ];
    }

    protected array $messages = [
        'nit.unique' => 'Ya existe un socio con ese NIT.',
        'nit.regex'  => 'El NIT/Cédula debe tener entre 6 y 20 dígitos.',

        'telefono_fijo.required'  => 'El teléfono fijo es obligatorio.',
        'telefono_fijo.regex'     => 'El teléfono fijo debe tener entre 7 y 15 dígitos.',
        'telefono_movil.required' => 'El teléfono móvil es obligatorio.',
        'telefono_movil.regex'    => 'El teléfono móvil debe tener entre 7 y 15 dígitos.',

        'correo.required' => 'El correo es obligatorio.',
        'correo.email'    => 'El correo no tiene un formato válido.',

        'direccion.required' => 'La dirección es obligatoria.',

        'condicion_pago_id.required' => 'La condición de pago es obligatoria.',
        'condicion_pago_id.exists'   => 'La condición de pago seleccionada no existe.',

        'Tipo.required'         => 'Debes seleccionar el tipo (Cliente/Proveedor).',
        'tipo_persona.required' => 'Debes seleccionar el tipo de persona.',
        'regimen_iva.required'  => 'Debes seleccionar el régimen de IVA.',
    ];

    /* ============================================================
     |  GUARDAR
     ============================================================ */
    public function save(): void
    {
        // Si la columna FK NO existe, mejor fallar elegante (para que "obligatorio" tenga sentido)
        if (!Schema::hasColumn('socio_negocios', 'condicion_pago_id')) {
            PendingToast::create()
                ->danger()
                ->title('Configuración faltante')
                ->message('La tabla socio_negocios no tiene la columna condicion_pago_id. Agrégala para guardar la condición de pago.');
            return;
        }

        $this->validate();

        try {
            DB::transaction(function () {

                $cp = CondicionPago::findOrFail($this->condicion_pago_id);

                // Base del socio
                $data = [
                    'razon_social'     => trim($this->razon_social),
                    'nit'              => trim((string) $this->nit),
                    'Tipo'             => strtoupper($this->Tipo ?? 'C'),

                    'telefono_fijo'    => trim((string) $this->telefono_fijo),
                    'telefono_movil'   => trim((string) $this->telefono_movil),
                    'correo'           => trim((string) $this->correo),
                    'direccion'        => trim((string) $this->direccion),

                    'municipio_barrio' => $this->municipio_barrio ? trim((string) $this->municipio_barrio) : null,
                    'saldo_pendiente'  => (float) ($this->saldo_pendiente ?: 0),

                    // fiscales
                    'tipo_persona'                => $this->tipo_persona,
                    'regimen_iva'                 => $this->regimen_iva,
                    'regimen_simple'              => (bool) $this->regimen_simple,
                    'municipio_id'                => $this->municipio_id,
                    'actividad_economica'         => $this->actividad_economica ? trim((string) $this->actividad_economica) : null,
                    'direccion_medios_magneticos' => $this->direccion_medios_magneticos ? trim((string) $this->direccion_medios_magneticos) : null,

                    // ✅ FK obligatoria
                    'condicion_pago_id' => (int) $this->condicion_pago_id,
                ];

                // ============================
                // LEGACY (si existe columna)
                // ============================
                if (Schema::hasColumn('socio_negocios', 'condicion_pago')) {
                    $data = array_merge($data, [
                        'condicion_pago'       => $cp->tipo, // contado|credito
                        'plazo_dias'           => $cp->tipo === 'credito' ? $cp->plazo_dias : null,
                        'interes_mora_pct'     => $cp->tipo === 'credito' ? $cp->interes_mora_pct : null,
                        'limite_credito'       => $cp->tipo === 'credito' ? $cp->limite_credito : null,
                        'tolerancia_mora_dias' => $cp->tipo === 'credito' ? $cp->tolerancia_mora_dias : null,
                        'dia_corte'            => $cp->tipo === 'credito' ? $cp->dia_corte : null,
                    ]);
                }

                // Crear socio
                $socio = SocioNegocio::create($data);

                // Snapshot JSON (si existe columna condiciones_pago)
                if (Schema::hasColumn('socio_negocios', 'condiciones_pago')) {
                    $socio->condiciones_pago = [
                        'id'                   => $cp->id,
                        'nombre'               => $cp->nombre,
                        'tipo'                 => $cp->tipo,
                        'plazo_dias'           => $cp->plazo_dias,
                        'interes_mora_pct'     => $cp->interes_mora_pct,
                        'limite_credito'       => $cp->limite_credito,
                        'tolerancia_mora_dias' => $cp->tolerancia_mora_dias,
                        'dia_corte'            => $cp->dia_corte,
                        'activo'               => (bool) ($cp->activo ?? true),
                    ];
                    $socio->save();
                }

                // Direcciones (solo si hay texto en dirección)
                $primerCreadoId   = null;
                $marcadaPrincipal = false;

                foreach ($this->direcciones as $d) {
                    $direccionTxt = trim((string) ($d['direccion'] ?? ''));
                    if ($direccionTxt === '') continue;

                    $fila = SocioDireccion::create([
                        'socio_negocio_id' => $socio->id,
                        'tipo'             => 'entrega',
                        'nombre'           => $d['nombre'] ?? null,
                        'direccion'        => $direccionTxt,
                        'referencia'       => $d['referencia'] ?? null,
                        'municipio_id'     => $d['municipio_id'] ?? null,
                        'es_principal'     => (bool) ($d['es_principal'] ?? false),
                    ]);

                    $primerCreadoId   ??= $fila->id;
                    $marcadaPrincipal = $marcadaPrincipal || (bool) $fila->es_principal;
                }

                if (!$marcadaPrincipal && $primerCreadoId) {
                    SocioDireccion::where('id', $primerCreadoId)->update(['es_principal' => true]);
                }
            });

            PendingToast::create()
                ->success()
                ->title('Socio creado')
                ->message('Socio creado correctamente.');

            $this->resetFormulario();
            $this->dispatch('socioCreado');

        } catch (\Throwable $e) {
            report($e);
            Log::error('Error creando socio', ['error' => $e->getMessage()]);

            PendingToast::create()
                ->danger()
                ->title('Error')
                ->message('No se pudo crear el socio. Revisa los datos e intenta de nuevo.');
        }
    }

    private function resetFormulario(): void
    {
        $this->reset([
            'razon_social',
            'nit',
            'Tipo',
            'telefono_fijo',
            'telefono_movil',
            'correo',
            'direccion',
            'municipio_barrio',
            'saldo_pendiente',
            'tipo_persona',
            'regimen_iva',
            'regimen_simple',
            'municipio_id',
            'actividad_economica',
            'direccion_medios_magneticos',
            'condicion_pago_id',
        ]);

        $this->Tipo = 'C';

        $this->direcciones = [[
            'id'           => null,
            'nombre'       => null,
            'direccion'    => null,
            'referencia'   => null,
            'municipio_id' => null,
            'es_principal' => true,
        ]];
    }

    /* ============================================================
     |  RENDER
     ============================================================ */
    public function render()
    {
        $condicionesPago = CondicionPago::query()
            ->when(
                Schema::hasColumn('condicion_pagos', 'activo'),
                fn ($q) => $q->where('activo', true)
            )
            ->orderBy('tipo')
            ->orderByRaw('COALESCE(plazo_dias,0)')
            ->orderBy('nombre')
            ->get([
                'id',
                'nombre',
                'tipo',
                'plazo_dias',
                'interes_mora_pct',
                'limite_credito',
                'tolerancia_mora_dias',
                'dia_corte',
                Schema::hasColumn('condicion_pagos', 'activo')
                    ? 'activo'
                    : DB::raw('1 as activo'),
            ]);

        return view('livewire.socio-negocio.create', [
            'municipios'      => $this->municipios,
            'condicionesPago' => $condicionesPago,
        ]);
    }
}
