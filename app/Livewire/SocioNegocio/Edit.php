<?php

namespace App\Livewire\SocioNegocio;

use App\Models\Municipio;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\SocioNegocio\SocioNegocio;
use App\Models\SocioNegocio\SocioDireccion;

use Masmerise\Toaster\PendingToast;

class Edit extends Component
{
    public $socioId;

    // ===== Datos generales
    public $razon_social;
    public $nit;
    public $tipo; // C | P
    public $telefono_fijo;
    public $telefono_movil;
    public $correo;
    public $direccion;
    public $municipio_barrio;
    public $saldo_pendiente;

    // ===== Datos fiscales
    public $tipo_persona = 'N';                  // N | J
    public $regimen_iva  = 'no_responsable';     // responsable | no_responsable
    public $regimen_simple = false;              // bool
    public $municipio_id = null;                 // municipio principal (catálogo)
    public $actividad_economica = null;          // CIIU
    public $direccion_medios_magneticos = null;  // texto

    // Catálogo
    public array $municipios = [];

    // ===== Direcciones (repeater)
    /**
     * [
     *   ['id'=>?, 'nombre'=>?, 'direccion'=>?, 'referencia'=>?, 'municipio_id'=>?, 'es_principal'=>bool],
     *   ...
     * ]
     */
    public array $direcciones = [];

    // Otros
    public bool $nitBloqueado = false;

    #[On('loadEditSocio')]
    public function loadEditSocio($id): void
    {
        $this->edit($id);
    }

    public function mount(): void
    {
        $this->cargarMunicipios();
    }

    private function cargarMunicipios(): void
    {
        $this->municipios = Municipio::query()
            ->orderBy('nombre')
            ->get(['id','nombre'])
            ->map(fn($m) => ['id' => $m->id, 'nombre' => $m->nombre])
            ->toArray();
    }

    public function edit(int $id): void
    {
        $this->socioId = $id;

        $socio = SocioNegocio::with(['direcciones'])->withCount('pedidos')->findOrFail($id);

        // Generales
        $this->razon_social     = $socio->razon_social;
        $this->nit              = $socio->nit;
        $this->tipo             = $socio->tipo;
        $this->telefono_fijo    = $socio->telefono_fijo;
        $this->telefono_movil   = $socio->telefono_movil;
        $this->correo           = $socio->correo;
        $this->direccion        = $socio->direccion;
        $this->municipio_barrio = $socio->municipio_barrio;
        $this->saldo_pendiente  = $socio->saldo_pendiente;

        // Fiscales
        $this->tipo_persona = $socio->tipo_persona ?? 'N';
        $this->regimen_iva  = $socio->regimen_iva  ?? 'no_responsable';
        $this->regimen_simple = (bool) ($socio->regimen_simple ?? false);
        $this->municipio_id = $socio->municipio_id;
        $this->actividad_economica = $socio->actividad_economica;
        $this->direccion_medios_magneticos = $socio->direccion_medios_magneticos;

        // Direcciones -> repeater
        $this->direcciones = $socio->direcciones->map(function ($d) {
            return [
                'id'           => $d->id,
                'nombre'       => $d->nombre,
                'direccion'    => $d->direccion,
                'referencia'   => $d->referencia,
                'municipio_id' => $d->municipio_id,
                'es_principal' => (bool) $d->es_principal,
            ];
        })->toArray();

        if (count($this->direcciones) === 0) {
            $this->direcciones = [[
                'id' => null,
                'nombre' => null,
                'direccion' => null,
                'referencia' => null,
                'municipio_id' => $this->municipio_id,
                'es_principal' => true,
            ]];
        }

        // NIT bloqueado si tiene pedidos
        $this->nitBloqueado = $socio->pedidos_count > 0;
    }

    // ===== Repeater handlers
    public function addDireccion(): void
    {
        $this->direcciones[] = [
            'id' => null,
            'nombre' => null,
            'direccion' => null,
            'referencia' => null,
            'municipio_id' => $this->municipio_id,
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

    // ===== Validación
    protected function rules(): array
    {
        return [
            // Generales
            'razon_social'     => ['required','string','max:190'],
            'nit'              => array_filter([
                'required','regex:/^\d{6,20}$/',
                $this->nitBloqueado ? null : Rule::unique('socio_negocios','nit')->ignore($this->socioId),
            ]),
            'tipo'             => ['required','in:C,P'],
            'telefono_fijo'    => ['nullable','regex:/^\d{7,15}$/'],
            'telefono_movil'   => ['nullable','regex:/^\d{7,15}$/'],
            'correo'           => ['required','email','max:190'],
            'direccion'        => ['required','string','max:190'],
            'municipio_barrio' => ['nullable','string','max:190'],
            'saldo_pendiente'  => ['nullable','numeric','min:0'],

            // Fiscales
            'tipo_persona' => ['required','in:N,J'],
            'regimen_iva'  => ['required','in:responsable,no_responsable'],
            'regimen_simple' => ['boolean'],
            'municipio_id' => ['nullable','integer'],
            'actividad_economica' => ['nullable','string','max:20'],
            'direccion_medios_magneticos' => ['nullable','string','max:190'],

            // Direcciones
            'direcciones' => ['array'],
            'direcciones.*.nombre'       => ['nullable','string','max:120'],
            'direcciones.*.direccion'    => ['nullable','string','max:190'],
            'direcciones.*.referencia'   => ['nullable','string','max:190'],
            'direcciones.*.municipio_id' => ['nullable','integer'],
            'direcciones.*.es_principal' => ['boolean'],
        ];
    }

    protected $messages = [
        'nit.regex' => 'El NIT/Cédula debe tener entre 6 y 20 dígitos.',
    ];

    // ===== Guardar
    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $socio = SocioNegocio::with('pedidos','direcciones')->findOrFail($this->socioId);

            // Seguridad: no permitir cambiar NIT si tiene pedidos
            if ($socio->pedidos()->exists() && $this->nit !== $socio->nit) {
                $this->nit = $socio->nit; // revertimos en UI
                PendingToast::create()->error()->message('No puedes modificar el NIT de un socio con pedidos registrados.')->duration(7000);
                session()->flash('error','No puedes modificar el NIT de un socio con pedidos registrados.');
                return;
            }

            // Actualizar socio
            $socio->update([
                'razon_social'     => $this->razon_social,
                'nit'              => $this->nit,
                'tipo'             => $this->tipo,
                'telefono_fijo'    => $this->telefono_fijo,
                'telefono_movil'   => $this->telefono_movil,
                'correo'           => $this->correo,
                'direccion'        => $this->direccion,
                'municipio_barrio' => $this->municipio_barrio,
                'saldo_pendiente'  => $this->saldo_pendiente ?: 0,

                // Fiscales
                'tipo_persona' => $this->tipo_persona,
                'regimen_iva'  => $this->regimen_iva,
                'regimen_simple' => (bool) $this->regimen_simple,
                'municipio_id' => $this->municipio_id,
                'actividad_economica' => $this->actividad_economica,
                'direccion_medios_magneticos' => $this->direccion_medios_magneticos,
            ]);

            // ==== Sincronizar direcciones
            $existingIds = $socio->direcciones->pluck('id')->all();
            $keepIds = [];
            $savedIds = [];
            $principalId = null;

            foreach ($this->direcciones as $row) {
                $dirTxt = trim((string)($row['direccion'] ?? ''));
                if ($dirTxt === '') {
                    continue; // ignorar filas vacías
                }

                $payload = [
                    'tipo'        => 'entrega',
                    'nombre'      => $row['nombre'] ?? null,
                    'direccion'   => $dirTxt,
                    'referencia'  => $row['referencia'] ?? null,
                    'municipio_id'=> $row['municipio_id'] ?? null,
                    'es_principal'=> (bool) ($row['es_principal'] ?? false),
                ];

                if (!empty($row['id'])) {
                    $dir = SocioDireccion::where('socio_negocio_id', $socio->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($dir) {
                        $dir->update($payload);
                        $keepIds[] = $dir->id;
                        $savedIds[] = $dir->id;
                        if ($payload['es_principal']) $principalId = $dir->id;
                    }
                } else {
                    $dir = SocioDireccion::create(array_merge($payload, [
                        'socio_negocio_id' => $socio->id,
                    ]));
                    $keepIds[] = $dir->id;
                    $savedIds[] = $dir->id;
                    if ($payload['es_principal']) $principalId = $dir->id;
                }
            }

            // Borrar las que el usuario quitó
            $toDelete = array_diff($existingIds, $keepIds);
            if (!empty($toDelete)) {
                SocioDireccion::where('socio_negocio_id', $socio->id)
                    ->whereIn('id', $toDelete)
                    ->delete();
            }

            // Normalizar principal: si ninguna marcada, dejar la primera guardada
            if (!$principalId && !empty($savedIds)) {
                $principalId = $savedIds[0];
            }

            // Dejar una sola principal
            if (!empty($savedIds)) {
                SocioDireccion::where('socio_negocio_id', $socio->id)
                    ->whereIn('id', $savedIds)
                    ->update(['es_principal' => false]);

                SocioDireccion::where('socio_negocio_id', $socio->id)
                    ->where('id', $principalId)
                    ->update(['es_principal' => true]);
            }
        });

        PendingToast::create()->success()->message('Socio de negocio actualizado correctamente.')->duration(5000);
        session()->flash('message','Socio de negocio actualizado correctamente.');

        $this->dispatch('socioActualizado');
        $this->dispatch('cerrar-modal-edit');
    }

    public function render()
    {
        return view('livewire.socio-negocio.edit', [
            'municipios' => $this->municipios,
        ]);
    }
}
