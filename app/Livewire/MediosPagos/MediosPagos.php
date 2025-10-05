<?php

namespace App\Livewire\MediosPagos;

use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Masmerise\Toaster\PendingToast;
use Throwable;

use App\Models\MediosPago\MedioPagos;
use App\Models\MediosPago\MedioPagoCuenta;
use App\Models\CuentasContables\PlanCuentas;

class MediosPagos extends Component
{
    /** Listado + filtros */
    public $items;
    public array $filters = [
        'q'      => '',
        'activo' => '', // '', '1', '0'
    ];

    /** Modal crear/editar */
    public bool $showModal = false;
    public ?int $editingId = null;

    /** Campos del formulario */
    public string $codigo = '';
    public string $nombre = '';
    public bool $activo   = true;
    public int  $orden    = 1;

    /** Cuenta contable asociada (1–1) */
    public ?int $plan_cuentas_id = null;
    public $cuentasPUC; // catálogo para el <select>

    /** Confirmar eliminar */
    public ?int $confirmingDeleteId = null;

    /* ================= Ciclo de vida ================= */
    public function mount(): void
    {
        try {
            // Catálogo de cuentas imputables (ajusta si quieres todas)
            $this->cuentasPUC = PlanCuentas::imputables()
                ->orderBy('codigo')
                ->get(['id','codigo','nombre']);

            $this->items = collect(); // init
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo cargar el catálogo de cuentas.');
            $this->cuentasPUC = collect();
            $this->items = collect();
        }
    }

    public function render()
    {
        try {
            $this->items = $this->queryItems();
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudieron cargar los medios de pago.');
            $this->items = collect();
        }

        return view('livewire.medios-pagos.medios-pagos', [
            'items'      => $this->items,
            'cuentasPUC' => $this->cuentasPUC,
        ]);
    }

    /** Construye la consulta del listado con filtros */
    protected function queryItems()
    {
        try {
            $q = MedioPagos::query()
                ->with(['cuenta.cuentaPUC']) // carga la relación 1–1
                ->orderBy('orden')
                ->orderBy('id');

            if ($t = trim($this->filters['q'] ?? '')) {
                $q->where(fn($w) => $w->where('codigo','like',"%{$t}%")
                                      ->orWhere('nombre','like',"%{$t}%"));
            }

            if ($this->filters['activo'] !== '') {
                $q->where('activo', (bool) $this->filters['activo']);
            }

            return $q->get();
        } catch (Throwable $e) {
            $this->handleException($e, 'Ocurrió un error al listar los medios de pago.');
            return collect();
        }
    }

    /* ================= Reglas ================= */
    protected function rules(): array
    {
        return [
            'codigo' => ['required','string','max:50', Rule::unique('medio_pagos','codigo')->ignore($this->editingId)],
            'nombre' => ['required','string','max:100'],
            'orden'  => ['integer','min:1'],
            'activo' => ['boolean'],
            // La cuenta puede ser opcional; si la quieres obligatoria, cambia a 'required|exists:plan_cuentas,id'
            'plan_cuentas_id' => ['nullable','exists:plan_cuentas,id'],
        ];
    }

    /* ================= Acciones UI ================= */
    public function abrirCrear(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function abrirEditar(int $id): void
    {
        try {
            $this->resetForm();

            $row = MedioPagos::with('cuenta')->findOrFail($id);

            $this->editingId       = $row->id;
            $this->codigo          = $row->codigo;
            $this->nombre          = $row->nombre;
            $this->activo          = (bool)$row->activo;
            $this->orden           = (int)$row->orden;
            $this->plan_cuentas_id = $row->cuenta?->plan_cuentas_id; // trae cuenta asociada

            $this->showModal = true;
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo abrir el registro para edición.', ['id' => $id]);
        }
    }

    public function guardar(): void
    {
        try {
            $this->validate();

            DB::transaction(function () {
                $data = [
                    'codigo' => $this->codigo,
                    'nombre' => $this->nombre,
                    'activo' => $this->activo,
                    'orden'  => $this->orden,
                ];

                if ($this->editingId) {
                    $medio = MedioPagos::findOrFail($this->editingId);
                    $medio->update($data);
                } else {
                    $medio = MedioPagos::create($data);
                    $this->editingId = $medio->id;
                }

                // === Guardar/actualizar la CUENTA asociada (1–1) ===
                if ($this->plan_cuentas_id) {
                    MedioPagoCuenta::updateOrCreate(
                        ['medio_pago_id' => $this->editingId],
                        ['plan_cuentas_id' => $this->plan_cuentas_id]
                    );
                } else {
                    // Si se deja vacío, eliminar la fila (asociación)
                    MedioPagoCuenta::where('medio_pago_id', $this->editingId)->delete();
                }
            });

            $msg = $this->editingId ? 'Medio de pago guardado.' : 'Medio de pago creado.';
            PendingToast::create()->success()->message($msg)->duration(3000);

            $this->showModal = false;
            $this->resetForm();

        } catch (ValidationException $e) {
            // Deja que Livewire muestre los errores de validación por campo
            throw $e;
        } catch (Throwable $e) {
            $this->handleException($e, 'Error al guardar el medio de pago.');
        }
    }

    public function confirmarEliminar(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function eliminar(): void
    {
        if (!$this->confirmingDeleteId) return;

        try {
            DB::transaction(function () {
                // Elimina primero la asociación 1–1 (por claridad)
                MedioPagoCuenta::where('medio_pago_id', $this->confirmingDeleteId)->delete();
                MedioPagos::findOrFail($this->confirmingDeleteId)->delete();
            });

            $this->confirmingDeleteId = null;
            PendingToast::create()->success()->message('Medio de pago eliminado.')->duration(2500);
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo eliminar el medio de pago.', [
                'id' => $this->confirmingDeleteId
            ]);
        }
    }

    public function toggleActivo(int $id): void
    {
        try {
            $row = MedioPagos::findOrFail($id);
            $row->activo = !$row->activo;
            $row->save();

            PendingToast::create()->info()->message('Estado actualizado.')->duration(2000);
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo cambiar el estado.', ['id' => $id]);
        }
    }

    /* ================= Helpers ================= */
    private function resetForm(): void
    {
        $this->reset([
            'editingId','codigo','nombre','activo','orden','plan_cuentas_id'
        ]);
        $this->activo = true;
        $this->orden  = 1;
    }

    /**
     * Log centralizado + toast de error.
     */
    private function handleException(Throwable $e, string $userMessage, array $context = []): void
    {
        Log::error($userMessage, array_merge($context, [
            'component' => static::class,
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ]));

        PendingToast::create()->danger()->message($userMessage)->duration(3500);

        // También puedes mostrar un error no asociado a campo:
        // $this->addError('general', $userMessage);
    }
}
