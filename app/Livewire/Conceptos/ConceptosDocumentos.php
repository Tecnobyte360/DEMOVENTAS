<?php

namespace App\Livewire\Conceptos;

use App\Models\Conceptos\ConceptoDocumento;
use App\Models\Conceptos\ConceptoDocumentoCuenta;

use App\Models\CuentasContables\PlanCuentas;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ConceptosDocumentos extends Component
{
     use WithPagination;

    protected string $paginationTheme = 'tailwind';

    /* ========= Filtros / Lista ========= */
    public string $search = '';
    public string $tipoFiltro = '';
    public bool   $soloActivos = true;
    public int    $perPage = 12;

    /* ========= Modal / Form ========= */
    public bool $showModal = false;

    public ?int   $concepto_id = null;
    public string $codigo = '';
    public string $nombre = '';
    public string $tipo   = 'entrada';   // entrada | salida | ajuste
    public string $descripcion = '';
    public bool   $activo = true;

    /* ========= Selector de cuentas ========= */
    public string $buscarCuenta = '';
    /** Catálogo mostrado en el modal (en render) */
    public $cuentasCatalogo = [];
    /** Mapa de cuentas seleccionadas (por id) con metadatos */
    public array $cuentasSeleccionadas = [
        // plan_cuenta_id => ['rol'=>?, 'naturaleza'=>?, 'porcentaje'=>?, 'prioridad'=>?]
    ];

    /** Sugerencias de rol (ajústalas a tu operación) */
    public array $rolesSugeridos = [
        'gasto', 'ingreso', 'costo', 'inventario',
        'gasto_devolucion', 'ingreso_devolucion'
    ];

    /* ========= Reglas ========= */
    protected function rules(): array
    {
        return [
            'codigo'      => [
                'required','string','max:50',
                Rule::unique('conceptos_documentos','codigo')->ignore($this->concepto_id)
            ],
            'nombre'      => ['required','string','max:255'],
            'tipo'        => ['required', Rule::in(['entrada','salida','ajuste'])],
            'descripcion' => ['nullable','string','max:1000'],
            'activo'      => ['boolean'],
        ];
    }

    protected array $messages = [
        'codigo.required' => 'El código es obligatorio.',
        'codigo.unique'   => 'Ya existe un concepto con ese código.',
        'nombre.required' => 'El nombre es obligatorio.',
        'tipo.in'         => 'Tipo inválido.',
    ];

    /* ========= Acciones de lista ========= */
    public function updatingSearch()      { $this->resetPage(); }
    public function updatingTipoFiltro()  { $this->resetPage(); }
    public function updatingSoloActivos() { $this->resetPage(); }

    /* ========= Crear / Editar ========= */
    public function crear(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editar(int $id): void
    {
        $this->resetForm();

        $c = ConceptoDocumento::findOrFail($id);
        $this->concepto_id = $c->id;
        $this->codigo      = (string) $c->codigo;
        $this->nombre      = (string) $c->nombre;
        $this->tipo        = (string) $c->tipo;
        $this->descripcion = (string) ($c->descripcion ?? '');
        $this->activo      = (bool) $c->activo;

        // Cargar lo asociado en la pivote
        $rows = ConceptoDocumentoCuenta::where('concepto_documento_id', $c->id)->get();
        foreach ($rows as $r) {
            $this->cuentasSeleccionadas[$r->plan_cuenta_id] = [
                'rol'        => $r->rol,
                'naturaleza' => $r->naturaleza,
                'porcentaje' => $r->porcentaje,
                'prioridad'  => $r->prioridad,
            ];
        }

        $this->showModal = true;
    }

    public function eliminar(int $id): void
    {
        DB::transaction(function () use ($id) {
            ConceptoDocumentoCuenta::where('concepto_documento_id', $id)->delete();
            ConceptoDocumento::where('id', $id)->delete();
        });

        session()->flash('ok', 'Concepto eliminado.');
        $this->resetPage();
    }

    /* ========= Guardar ========= */
    public function guardar(): void
    {
        $this->validate();

        DB::transaction(function () {
            $concepto = ConceptoDocumento::updateOrCreate(
                ['id' => $this->concepto_id],
                [
                    'codigo'      => trim($this->codigo),
                    'nombre'      => trim($this->nombre),
                    'tipo'        => $this->tipo,
                    'descripcion' => $this->descripcion ?: null,
                    'activo'      => $this->activo,
                ]
            );
            $this->concepto_id = $concepto->id;

            // Sincronizar pivote: primero quitamos lo que ya no está seleccionado
            $idsVigentes = array_keys($this->cuentasSeleccionadas);
            ConceptoDocumentoCuenta::where('concepto_documento_id', $concepto->id)
                ->whereNotIn('plan_cuenta_id', $idsVigentes ?: [0])
                ->delete();

            // Upsert para lo seleccionado
            foreach ($this->cuentasSeleccionadas as $pcId => $meta) {
                ConceptoDocumentoCuenta::updateOrCreate(
                    [
                        'concepto_documento_id' => $concepto->id,
                        'plan_cuenta_id'        => (int)$pcId,
                    ],
                    [
                        'rol'        => $meta['rol']        ?? null,
                        'naturaleza' => $meta['naturaleza'] ?? null,
                        'porcentaje' => $meta['porcentaje'] !== '' ? (float)$meta['porcentaje'] : null,
                        'prioridad'  => isset($meta['prioridad']) ? (int)$meta['prioridad'] : 0,
                    ]
                );
            }
        });

        session()->flash('ok', $this->concepto_id ? 'Concepto guardado.' : 'Concepto creado.');
        $this->showModal = false;
        $this->resetPage();
    }

    /* ========= Helpers del modal ========= */

    /** Marcar / desmarcar cuenta */
    public function toggleCuenta(int $planCuentaId): void
    {
        if (isset($this->cuentasSeleccionadas[$planCuentaId])) {
            unset($this->cuentasSeleccionadas[$planCuentaId]);
            return;
        }
        // Defaults al seleccionar
        $this->cuentasSeleccionadas[$planCuentaId] = [
            'rol'        => null,
            'naturaleza' => null,
            'porcentaje' => null,
            'prioridad'  => 0,
        ];
    }

    /** Cambiar metadatos de la cuenta seleccionada */
    public function setMetaCuenta(int $planCuentaId, string $campo, $valor): void
    {
        if (!isset($this->cuentasSeleccionadas[$planCuentaId])) return;

        if ($campo === 'porcentaje') {
            $valor = ($valor === '' || $valor === null) ? null : (float)$valor;
        } elseif ($campo === 'prioridad') {
            $valor = (int) $valor;
        } elseif ($campo === 'rol') {
            $valor = $valor ?: null;
        } elseif ($campo === 'naturaleza') {
            $valor = in_array($valor, ['debito','credito'], true) ? $valor : null;
        }

        $this->cuentasSeleccionadas[$planCuentaId][$campo] = $valor;
    }

    private function resetForm(): void
    {
        $this->reset([
            'concepto_id','codigo','nombre','tipo','descripcion','activo',
            'buscarCuenta','cuentasSeleccionadas'
        ]);
        $this->tipo   = 'entrada';
        $this->activo = true;
        $this->cuentasSeleccionadas = [];
    }

    /* ========= Render ========= */
    public function render()
    {
        // Lista de conceptos con filtros
        $conceptos =ConceptoDocumento::query()
            ->when($this->search, function ($q) {
                $t = trim($this->search);
                $q->where(function($qq) use ($t) {
                    $qq->where('codigo','like',"%{$t}%")
                       ->orWhere('nombre','like',"%{$t}%")
                       ->orWhere('descripcion','like',"%{$t}%");
                });
            })
            ->when($this->tipoFiltro !== '', fn($q) => $q->where('tipo',$this->tipoFiltro))
            ->when($this->soloActivos, fn($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->paginate($this->perPage);

        // Catálogo de PUC para el modal (filtrado por texto)
        $this->cuentasCatalogo = PlanCuentas::query()
            ->when($this->buscarCuenta, function ($q) {
                $t = trim($this->buscarCuenta);
                $q->where(function($w) use ($t){
                    $w->where('codigo','like',"%{$t}%")
                      ->orWhere('nombre','like',"%{$t}%");
                });
            })
            ->where('cuenta_activa', true)
            ->orderBy('codigo')
            ->limit(100)
            ->get();

        return view('livewire.conceptos.conceptos-documentos', [
            'conceptos'        => $conceptos,
            'cuentasCatalogo'  => $this->cuentasCatalogo,
        ]);
    }
}

