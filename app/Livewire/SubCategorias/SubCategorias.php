<?php

namespace App\Livewire\SubCategorias;

use Livewire\Component;
use Masmerise\Toaster\PendingToast;
use App\Models\Categorias\Subcategoria;
use App\Models\Categorias\Categoria;
use App\Models\Productos\ProductoCuentaTipo;
use App\Models\CuentasContables\PlanCuentas;

class SubCategorias extends Component
{
    public $subcategorias;
    public $categoria_id, $nombre, $descripcion, $activo = true, $subcategoria_id;
    public $isEdit = false;

    /** ===== Plan de cuentas por Subcategoría ===== */
    public bool $showCuentasModal = false;
    public ?int $subcategoria_id_en_modal = null;

    /** Catálogos */
    public $tiposCuenta;   // ProductoCuentaTipo[]
    public $cuentasPUC;    // PlanCuentas[]

    /** Selección del modal: [tipo_id => plan_cuentas_id|null] */
    public array $cuentasPorTipo = [];

    public function mount(): void
    {
        // Tipos de cuenta (si tienes scope activos(), genial; si no, reemplázalo por ProductoCuentaTipo::query())
        $this->tiposCuenta = ProductoCuentaTipo::query()
            ->orderBy('orden')->orderBy('id')
            ->get(['id','nombre','obligatorio']);

        // TODAS las cuentas (si prefieres sólo imputables, usa ->imputables())
        $this->cuentasPUC = PlanCuentas::query()
            ->orderBy('codigo')
            ->get(['id','codigo','nombre','nivel']);

        // Inicializar selección vacía
        foreach ($this->tiposCuenta as $t) {
            $this->cuentasPorTipo[$t->id] = null;
        }
    }

    public function render()
    {
        $this->subcategorias = Subcategoria::with(['categoria','cuentas.tipo','cuentas.cuentaPUC'])->get();

        return view('livewire.sub-categorias.sub-categorias', [
            'categorias'  => Categoria::all(),
            'tiposCuenta' => $this->tiposCuenta,
            'cuentasPUC'  => $this->cuentasPUC,
        ]);
    }

    /** ===== CRUD ===== */
    public function store(): void
    {
        $this->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre'       => 'required|string|max:255',
        ]);

        Subcategoria::create($this->only(['categoria_id','nombre','descripcion','activo']));
        $this->resetInput();

        PendingToast::create()->success()->message('Subcategoría registrada correctamente.')->duration(4000);
    }

    public function edit($id): void
    {
        $sub = Subcategoria::findOrFail($id);
        $this->fill($sub->only(['categoria_id','nombre','descripcion','activo']));
        $this->subcategoria_id = $id;
        $this->isEdit = true;
    }

    public function update(): void
    {
        $this->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre'       => 'required|string|max:255',
        ]);

        Subcategoria::findOrFail($this->subcategoria_id)
            ->update($this->only(['categoria_id','nombre','descripcion','activo']));

        $this->resetInput();

        PendingToast::create()->success()->message('Subcategoría actualizada correctamente.')->duration(4000);
    }

    public function delete($id): void
    {
        Subcategoria::destroy($id);
        PendingToast::create()->success()->message('Subcategoría eliminada.')->duration(3000);
    }

    private function resetInput(): void
    {
        $this->reset(['categoria_id','nombre','descripcion','activo','subcategoria_id','isEdit']);
    }

    /** ===== Modal de cuentas (OPCIONALES) ===== */
    public function abrirModalCuentas(int $id): void
    {
        $this->subcategoria_id_en_modal = $id;

        // Pre-cargar selección existente
        $sub = Subcategoria::with('cuentas')->findOrFail($id);

        $this->cuentasPorTipo = [];
        foreach ($this->tiposCuenta as $t) {
            $existente = $sub->cuentas->firstWhere('tipo_id', $t->id);
            $this->cuentasPorTipo[$t->id] = $existente?->plan_cuentas_id;
        }

        $this->showCuentasModal = true;
    }

    public function cerrarModalCuentas(): void
    {
        $this->showCuentasModal = false;
    }

    public function limpiarCuentaTipo(int $tipoId): void
    {
        $this->cuentasPorTipo[$tipoId] = null;
    }

    public function guardarCuentas(): void
    {
        if (!$this->subcategoria_id_en_modal) return;

        // ✅ Todas OPCIONALES
        $rules = [];
        foreach ($this->tiposCuenta as $t) {
            $rules["cuentasPorTipo.{$t->id}"] = 'nullable|exists:plan_cuentas,id';
        }
        $this->validate($rules);

        $sub = Subcategoria::findOrFail($this->subcategoria_id_en_modal);

        foreach ($this->cuentasPorTipo as $tipoId => $pucId) {
            if ($pucId) {
                $sub->cuentas()->updateOrCreate(
                    ['tipo_id' => (int)$tipoId],
                    ['plan_cuentas_id' => (int)$pucId]
                );
            } else {
                // si quedó vacío, elimina la fila (tipo opcional)
                $sub->cuentas()->where('tipo_id', (int)$tipoId)->delete();
            }
        }

        $this->showCuentasModal = false;
        PendingToast::create()->success()->message('Cuentas de la subcategoría guardadas.')->duration(3500);
    }
}
