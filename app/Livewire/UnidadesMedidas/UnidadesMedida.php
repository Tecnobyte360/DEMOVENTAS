<?php

namespace App\Livewire\UnidadesMedida;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UnidadMedida;
use Illuminate\Validation\Rule;
use Masmerise\Toaster\PendingToast;

class UnidadesMedida extends Component
{
    use WithPagination;

    // Form
    public $unidad_id = null;
    public $codigo = '';
    public $nombre = '';
    public $simbolo = '';
    public $tipo = 'otro';
    public $activo = true;

    public $isEdit = false;

    // UI
    public $search = '';
    public $perPage = 10;

    public array $tipos = ['masa','volumen','longitud','tiempo','otro'];

    protected $queryString = ['search'];

    public function render()
    {
        $unidades = UnidadesMedida::query()
            ->buscar($this->search)
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.unidades-medida.unidades-medida', [
            'unidades' => $unidades
        ]);
    }

    /* ===== CRUD ===== */

    public function store()
    {
        $data = $this->validate($this->rules());

        $um =UnidadesMedida::create($data);

        $this->resetForm();
        PendingToast::create()->success()->message('Unidad creada correctamente.')->duration(3000);

        // Opcional: refrescar página 1
        $this->resetPage();
    }

    public function edit(int $id)
    {
        $um = UnidadesMedida::findOrFail($id);
        $this->unidad_id = $um->id;
        $this->codigo    = $um->codigo;
        $this->nombre    = $um->nombre;
        $this->simbolo   = $um->simbolo;
        $this->tipo      = $um->tipo ?? 'otro';
        $this->activo    = (bool) $um->activo;
        $this->isEdit    = true;
    }

    public function update()
    {
        $um = UnidadesMedida::findOrFail($this->unidad_id);
        $data = $this->validate($this->rules(true));

        $um->update($data);

        $this->resetForm();
        PendingToast::create()->success()->message('Unidad actualizada.')->duration(3000);
    }

    public function toggleActivo(int $id)
    {
        $um = UnidadesMedida::findOrFail($id);
        $um->update(['activo' => !$um->activo]);
        PendingToast::create()->success()->message('Estado actualizado.')->duration(2500);
    }

    public function destroy(int $id)
    {
        $um = UnidadesMedida::findOrFail($id);
        try {
            $um->delete(); // si productos.unidad_medida_id es nullable + nullOnDelete, se permite
            PendingToast::create()->success()->message('Unidad eliminada.')->duration(2500);
            $this->resetPage();
        } catch (\Throwable $e) {
            // Si tu FK no permite borrar, sugiere inactivar:
            PendingToast::create()->error()->message('No se pudo eliminar. Inactívala si está en uso.')->duration(6000);
        }
    }

    /* ===== Helpers ===== */

    private function rules(bool $isUpdate = false): array
    {
        $id = $this->unidad_id ?? 'NULL';

        return [
            'codigo' => [
                'required','string','max:10',
                Rule::unique('unidades_medida','codigo')->ignore($isUpdate ? $id : null),
            ],
            'nombre'  => ['required','string','max:150'],
            'simbolo' => ['nullable','string','max:10'],
            'tipo'    => ['nullable', Rule::in($this->tipos)],
            'activo'  => ['boolean'],
        ];
    }

    private function resetForm()
    {
        $this->reset(['unidad_id','codigo','nombre','simbolo','tipo','activo','isEdit']);
        $this->tipo = 'otro';
        $this->activo = true;
    }

    public function updated($prop)
    {
        $this->validateOnly($prop, $this->rules($this->isEdit));
        if ($prop === 'codigo') $this->codigo = strtoupper($this->codigo ?? '');
        if ($prop === 'simbolo') $this->simbolo = $this->simbolo ? trim($this->simbolo) : null;
    }
}
