<?php

namespace App\Livewire\NormasReparto;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Throwable;

// Modelos
use App\Models\NormasReparto\NormasReparto as NormaModel;
use App\Models\NormasReparto\NormasRepartoDetalle as DetalleModel;

// Toaster
use Masmerise\Toaster\PendingToast;

class NormasReparto extends Component
{
    use WithPagination;

    /** Estado de edición / UI */
    public ?int $editId = null;     // null = creando, id = editando
    public bool $showForm = false;  // por si abres modal (opcional)

    /** Cabecera */
    public $codigo, $descripcion, $dimension = 'CENTRO_DE_OPERACIONES';
    public $valido_desde, $valido_hasta;
    public $valido = true, $imputacion_directa = false, $asignar_importes_fijos = false;

    /** Detalles */
    public $detalles = [
        ['codigo_centro' => '', 'nombre_centro' => '', 'valor' => 0],
    ];

    /* ======================
     * Ciclo de vida
     * ======================*/
    public function mount(): void
    {
        try {
            $this->valido_desde = now()->toDateString();
          
        } catch (Throwable $e) {
            Log::error("NormasReparto mount: {$e->getMessage()}");
           
        }
    }

    /* ======================
     * Acciones de UI
     * ======================*/
    public function addLinea(): void
    {
        try {
            $this->detalles[] = ['codigo_centro' => '', 'nombre_centro' => '', 'valor' => 0];
            PendingToast::create()->success()->message('Línea añadida.')->duration(2500);
        } catch (Throwable $e) {
            Log::error("addLinea: {$e->getMessage()}");
         
        }
    }

    public function removeLinea(int $i): void
    {
        try {
            unset($this->detalles[$i]);
            $this->detalles = array_values($this->detalles);
            PendingToast::create()->warning()->message('Línea eliminada.')->duration(2500);
        } catch (Throwable $e) {
            Log::error("removeLinea: {$e->getMessage()}");
            PendingToast::create()->error()->message('No se pudo eliminar la línea.')->duration(6000);
        }
    }

    /* ======================
     * Cargar para edición
     * ======================*/
    public function edit(int $id): void
    {
        try {
            $norma = NormaModel::with('detalles')->findOrFail($id);

            $this->editId                 = $norma->id;
            $this->codigo                 = $norma->codigo;
            $this->descripcion            = $norma->descripcion;
            $this->dimension              = $norma->dimension;
            $this->valido_desde           = optional($norma->valido_desde)->toDateString();
            $this->valido_hasta           = optional($norma->valido_hasta)->toDateString();
            $this->valido                 = (bool) $norma->valido;
            $this->imputacion_directa     = (bool) $norma->imputacion_directa;
            $this->asignar_importes_fijos = (bool) $norma->asignar_importes_fijos;

            $this->detalles = $norma->detalles->map(fn($d) => [
                'codigo_centro' => $d->codigo_centro,
                'nombre_centro' => $d->nombre_centro,
                'valor'         => (float) $d->valor,
            ])->toArray();

            $this->showForm = true; // si usas modal
            PendingToast::create()->success()->message('Norma cargada para edición.')->duration(3000);
        } catch (Throwable $e) {
            Log::error("edit({$id}): {$e->getMessage()}");
            PendingToast::create()->error()->message('No fue posible cargar la norma.')->duration(6000);
        }
    }

    /* ======================
     * Guardar (crear/actualizar)
     * ======================*/
    public function save(): void
    {
        // Regla única para 'codigo' en el esquema real (SQL Server)
        $rules = [
            'codigo'                   => [
                'required', 'string', 'max:50',
                Rule::unique('normas_repartos', 'codigo')->ignore($this->editId),
            ],
            'dimension'                => 'required|string',
            'valido_desde'             => 'required|date',
            'detalles.*.codigo_centro' => 'required|string|max:60',
            'detalles.*.valor'         => 'required|numeric|min:0',
        ];

        try {
            $this->validate($rules);
        } catch (ValidationException $e) {
            $msg = $e->validator->errors()->first() ?: 'Revisa los datos del formulario.';
            PendingToast::create()->error()->message($msg)->duration(6000);
            throw $e;
        }

        try {
            DB::transaction(function () {
                if ($this->editId) {
                    // UPDATE
                    $norma = NormaModel::findOrFail($this->editId);

                    $norma->update([
                        'codigo'                 => $this->codigo,
                        'descripcion'            => $this->descripcion,
                        'dimension'              => $this->dimension,
                        'valido_desde'           => $this->valido_desde,
                        'valido_hasta'           => $this->valido_hasta,
                        'valido'                 => $this->valido,
                        'imputacion_directa'     => $this->imputacion_directa,
                        'asignar_importes_fijos' => $this->asignar_importes_fijos,
                    ]);

                    // Estrategia simple: recrear detalles
                    $norma->detalles()->delete();
                    foreach ($this->detalles as $d) {
                        $norma->detalles()->create([
                            'codigo_centro' => $d['codigo_centro'],
                            'nombre_centro' => $d['nombre_centro'] ?? null,
                            'valor'         => $d['valor'],
                        ]);
                    }
                } else {
                    // CREATE
                    $norma = NormaModel::create([
                        'codigo'                 => $this->codigo,
                        'descripcion'            => $this->descripcion,
                        'dimension'              => $this->dimension,
                        'valido_desde'           => $this->valido_desde,
                        'valido_hasta'           => $this->valido_hasta,
                        'valido'                 => $this->valido,
                        'imputacion_directa'     => $this->imputacion_directa,
                        'asignar_importes_fijos' => $this->asignar_importes_fijos,
                    ]);

                    foreach ($this->detalles as $d) {
                        $norma->detalles()->create([
                            'codigo_centro' => $d['codigo_centro'],
                            'nombre_centro' => $d['nombre_centro'] ?? null,
                            'valor'         => $d['valor'],
                        ]);
                    }
                }
            });

            $msg = $this->editId ? 'Norma actualizada correctamente.' : 'Norma creada correctamente.';
            $this->resetForm();
            $this->showForm = false;

            PendingToast::create()->success()->message($msg)->duration(5000);
        } catch (Throwable $e) {
            Log::error("save (editId={$this->editId}): {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
            PendingToast::create()->error()->message('Error al guardar la norma.')->duration(8000);
        }
    }

    /* ======================
     * Reset
     * ======================*/
    public function resetForm(): void
    {
        try {
            $this->editId = null;
            $this->codigo = '';
            $this->descripcion = '';
            $this->dimension = 'CENTRO_DE_OPERACIONES';
            $this->valido_desde = now()->toDateString();
            $this->valido_hasta = null;
            $this->valido = true;
            $this->imputacion_directa = false;
            $this->asignar_importes_fijos = false;
            $this->detalles = [['codigo_centro' => '', 'nombre_centro' => '', 'valor' => 0]];

        
        } catch (Throwable $e) {
            Log::error("resetForm: {$e->getMessage()}");
           
        }
    }

    /* ======================
     * Render
     * ======================*/
    public function render()
    {
        try {
            $normas = NormaModel::withCount('detalles')
                ->latest('id') // usa ->latest() si tienes timestamps
                ->paginate(10);

            return view('livewire.normas-reparto.normas-reparto', compact('normas'));
        } catch (Throwable $e) {
            Log::error("render: {$e->getMessage()}");
            $empty = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(), 0, 10, request('page', 1),
                ['path' => request()->url(), 'query' => request()->query()]
            );
            return view('livewire.normas-reparto.normas-reparto', ['normas' => $empty]);
        }
    }
}
