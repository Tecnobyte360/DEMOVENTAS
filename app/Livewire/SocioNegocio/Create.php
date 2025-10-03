<?php

namespace App\Livewire\SocioNegocio;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Municipio;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\SocioNegocio\SocioDireccion;
use App\Models\CondicionPago\CondicionPago;

class Create extends Component
{
    // ===== Campos base =====
    public $razon_social, $nit, $Tipo;
    public $telefono_fijo, $telefono_movil, $correo, $direccion, $municipio_barrio;
    public $saldo_pendiente;

    // ===== Condición de pago (nuevo) =====
    public ?int $condicion_pago_id = null;

    // ===== Campos fiscales =====
    public $tipo_persona = 'N'; // N=Natural, J=Jurídica
    public $regimen_iva  = 'no_responsable'; // responsable | no_responsable
    public $regimen_simple = false;
    public $municipio_id = null; // municipio principal del socio
    public $actividad_economica = null;
    public $direccion_medios_magneticos = null;

    // Catálogo municipios para select
    public array $municipios = [];

    // ===== Repeater de direcciones =====
    /**
     * cada item:
     * [
     *   'id'           => null,
     *   'nombre'       => null,
     *   'direccion'    => null,
     *   'referencia'   => null,
     *   'municipio_id' => int|null,
     *   'es_principal' => bool,
     * ]
     */
    public array $direcciones = [];

    public function mount()
    {
        $this->cargarMunicipios();

        // arranque con una fila de dirección vacía
        $this->direcciones = [[
            'id' => null,
            'nombre' => null,
            'direccion' => null,
            'referencia' => null,
            'municipio_id' => null,
            'es_principal' => true, // la primera por defecto
        ]];
    }

    private function cargarMunicipios(): void
    {
        $modelClass = null;
        if (class_exists(Municipio::class)) {
            $modelClass = Municipio::class;
        }

        $q = $modelClass
            ? $modelClass::query()->orderBy('nombre')->get(['id','nombre'])
            : collect();

        $this->municipios = $q->map(fn($m)=>['id'=>$m->id, 'nombre'=>$m->nombre])->toArray();

        if (empty($this->municipios)) {
            $this->municipios = [
                ['id'=>1,'nombre'=>'Medellín'],
                ['id'=>2,'nombre'=>'Itagüí'],
                ['id'=>3,'nombre'=>'La Estrella'],
            ];
        }
    }

    // ====== Repeater handlers ======
    public function addDireccion(): void
    {
        $this->direcciones[] = [
            'id' => null,
            'nombre' => null,
            'direccion' => null,
            'referencia' => null,
            'municipio_id' => $this->municipio_id, // sugiere el principal
            'es_principal' => false,
        ];
    }

    public function removeDireccion(int $index): void
    {
        if (!isset($this->direcciones[$index])) return;

        $removedWasPrincipal = (bool)($this->direcciones[$index]['es_principal'] ?? false);
        unset($this->direcciones[$index]);
        $this->direcciones = array_values($this->direcciones);

        if ($removedWasPrincipal && count($this->direcciones) > 0) {
            foreach ($this->direcciones as $i => $d) { $this->direcciones[$i]['es_principal'] = false; }
            $this->direcciones[0]['es_principal'] = true;
        }
    }

    public function setPrincipal(int $index): void
    {
        foreach ($this->direcciones as $i => $d) {
            $this->direcciones[$i]['es_principal'] = ($i === $index);
        }
    }

    // ====== Validación ======
    protected function rules()
    {
        return [
            'razon_social' => ['required','string','max:190'],
            'nit'          => ['required','string','regex:/^\d{6,20}$/','unique:socio_negocios,nit'],
            'Tipo'         => ['required','in:C,P'],
            'telefono_fijo'  => ['nullable','string','regex:/^\d{7,15}$/'],
            'telefono_movil' => ['nullable','string','regex:/^\d{7,15}$/'],
            'correo'       => ['nullable','email','max:190'],
            'direccion'    => ['nullable','string','max:190'],
            'municipio_barrio' => ['nullable','string','max:190'],
            'saldo_pendiente'  => ['nullable','numeric','min:0'],

            // Condición de pago
            'condicion_pago_id' => ['nullable','integer','exists:condicion_pagos,id'],

            // Fiscales
            'tipo_persona' => ['required','in:N,J'],
            'regimen_iva'  => ['required','in:responsable,no_responsable'],
            'regimen_simple' => ['boolean'],
            'municipio_id' => ['nullable','integer'],
            'actividad_economica' => ['nullable','string','max:20'],
            'direccion_medios_magneticos' => ['nullable','string','max:190'],

            // Repeater direcciones
            'direcciones' => ['array'],
            'direcciones.*.nombre'       => ['nullable','string','max:120'],
            'direcciones.*.direccion'    => ['nullable','string','max:190'],
            'direcciones.*.referencia'   => ['nullable','string','max:190'],
            'direcciones.*.municipio_id' => ['nullable','integer'],
            'direcciones.*.es_principal' => ['boolean'],
        ];
    }

    protected $messages = [
        'nit.unique' => 'Ya existe un socio con ese NIT.',
        'nit.regex'  => 'El NIT/Cédula debe tener entre 6 y 20 dígitos.',
        'Tipo.required' => 'Debes seleccionar el tipo (Cliente/Proveedor).',
        'tipo_persona.required' => 'Debes seleccionar el tipo de persona.',
        'regimen_iva.required'  => 'Debes seleccionar el régimen de IVA.',
        'condicion_pago_id.exists' => 'La condición de pago seleccionada no existe.',
    ];

    // ====== Guardar ======
    public function save()
    {
        $this->validate();

        DB::transaction(function () {

            // Arma base de datos para crear
            $data = [
                'razon_social' => $this->razon_social,
                'nit'          => $this->nit,
                'Tipo'         => $this->Tipo, // respeta la columna con T mayúscula
                'telefono_fijo'  => $this->telefono_fijo,
                'telefono_movil' => $this->telefono_movil,
                'correo'       => $this->correo,
                'direccion'    => $this->direccion,
                'municipio_barrio' => $this->municipio_barrio,
                'saldo_pendiente'  => $this->saldo_pendiente ?: 0,

                // nuevos
                'tipo_persona' => $this->tipo_persona,
                'regimen_iva'  => $this->regimen_iva,
                'regimen_simple' => (bool) $this->regimen_simple,
                'municipio_id' => $this->municipio_id,
                'actividad_economica' => $this->actividad_economica,
                'direccion_medios_magneticos' => $this->direccion_medios_magneticos,
            ];

            // Si existe columna FK, agréguela
            if (Schema::hasColumn('socio_negocios', 'condicion_pago_id')) {
                $data['condicion_pago_id'] = $this->condicion_pago_id;
            }

            // Crear socio
            $socio = SocioNegocio::create($data);

            // Snapshot JSON (si existe columna condiciones_pago)
            if (Schema::hasColumn('socio_negocios', 'condiciones_pago') && $this->condicion_pago_id) {
                $cp = CondicionPago::find($this->condicion_pago_id);
                if ($cp) {
                    $socio->condiciones_pago = [
                        'id'                  => $cp->id,
                        'nombre'              => $cp->nombre,
                        'tipo'                => $cp->tipo,
                        'plazo_dias'          => $cp->plazo_dias,
                        'interes_mora_pct'    => $cp->interes_mora_pct,
                        'limite_credito'      => $cp->limite_credito,
                        'tolerancia_mora_dias'=> $cp->tolerancia_mora_dias,
                        'dia_corte'           => $cp->dia_corte,
                        'activo'              => (bool)$cp->activo,
                    ];
                    $socio->save();
                }
            }

            // Crear direcciones (solo las que tengan texto en 'direccion')
            $primerCreadoId = null;
            $marcadaPrincipal = false;

            foreach ($this->direcciones as $d) {
                $direccionTxt = trim((string)($d['direccion'] ?? ''));
                if ($direccionTxt === '') { continue; }

                $fila = SocioDireccion::create([
                    'socio_negocio_id' => $socio->id,
                    'tipo'        => 'entrega',
                    'nombre'      => $d['nombre'] ?? null,
                    'direccion'   => $direccionTxt,
                    'referencia'  => $d['referencia'] ?? null,
                    'municipio_id'=> $d['municipio_id'] ?? null,
                    'es_principal'=> (bool)($d['es_principal'] ?? false),
                ]);

                $primerCreadoId ??= $fila->id;
                $marcadaPrincipal = $marcadaPrincipal || (bool)$fila->es_principal;
            }

            // si ninguna quedó como principal, marca la primera creada
            if (!$marcadaPrincipal && $primerCreadoId) {
                SocioDireccion::where('id',$primerCreadoId)->update(['es_principal'=>true]);
            }
        });

        session()->flash('message','Socio creado correctamente.');

        // limpiar el formulario
        $this->reset([
            'razon_social','nit','Tipo','telefono_fijo','telefono_movil','correo','direccion',
            'municipio_barrio','saldo_pendiente','tipo_persona','regimen_iva','regimen_simple',
            'municipio_id','actividad_economica','direccion_medios_magneticos','condicion_pago_id',
        ]);
        $this->direcciones = [[
            'id'=>null,'nombre'=>null,'direccion'=>null,'referencia'=>null,'municipio_id'=>null,'es_principal'=>true
        ]];

        // notificar al padre (si quieres refrescar listados y cerrar modal)
        $this->dispatch('socioCreado');
    }

    public function render()
    {
        // Trae condiciones de pago activas para el select
        $condicionesPago = CondicionPago::query()
            ->where('activo', true)
            ->orderBy('tipo')
            ->orderByRaw('COALESCE(plazo_dias,0)')
            ->orderBy('nombre')
            ->get(['id','nombre','tipo','plazo_dias','interes_mora_pct','limite_credito','tolerancia_mora_dias','dia_corte','activo']);

        return view('livewire.socio-negocio.create', [
            'municipios'      => $this->municipios,
            'condicionesPago' => $condicionesPago,
        ]);
    }
}
