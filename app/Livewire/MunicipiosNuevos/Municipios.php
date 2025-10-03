<?php

namespace App\Livewire\MunicipiosNuevos;

use App\Models\Municipio;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Municipios extends Component
{

    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros/UI
    public string $search = '';
    public int $perPage = 10;

    // Modal + formulario
    public bool $showForm = false;
    public ?int $editingId = null;
    public ?string $codigo_dane = null;
    public ?string $nombre = null;
    public ?string $departamento = null;

    // Reset de paginación al cambiar filtros
    public function updatingSearch()  { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:120',
                Rule::unique('municipios', 'nombre')->ignore($this->editingId, 'id'),
            ],
            'codigo_dane' => [
                'nullable', 'string', 'max:20',
                Rule::unique('municipios', 'codigo_dane')->ignore($this->editingId, 'id'),
            ],
            'departamento' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->resetValidation();

        $m = Municipios::findOrFail($id);
        $this->editingId    = $m->id;
        $this->codigo_dane  = $m->codigo_dane;
        $this->nombre       = $m->nombre;
        $this->departamento = $m->departamento;

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            // update
            $m = Municipio::findOrFail($this->editingId);
            $m->update($data);
            session()->flash('message', 'Municipio actualizado.');
        } else {
            // create
            Municipio::create($data);
            session()->flash('message', 'Municipio creado.');
        }

        $this->closeForm();
        $this->resetForm();
        // Opcional: volver a la primera página
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        try {
            Municipio::findOrFail($id)->delete();
            session()->flash('message', 'Municipio eliminado.');
            // Si eliminaste el último de la página, corrige la paginación
            if ($this->page > 1 && $this->currentPageEmpty()) {
                $this->previousPage();
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'No se puede eliminar: hay registros relacionados.');
        }
    }

    private function currentPageEmpty(): bool
    {
        return Municipio::when($this->search, function ($q) {
                $t = "%{$this->search}%";
                $q->where('nombre', 'like', $t)
                  ->orWhere('codigo_dane', 'like', $t)
                  ->orWhere('departamento', 'like', $t);
            })
            ->orderBy('nombre')
            ->paginate($this->perPage, page: $this->page)
            ->isEmpty();
    }

    private function resetForm(): void
    {
        $this->editingId    = null;
        $this->codigo_dane  = null;
        $this->nombre       = null;
        $this->departamento = null;
    }

    public function render()
    {
        $rows = Municipio::query()
            ->when($this->search, function ($q) {
                $t = "%{$this->search}%";
                $q->where(function ($qq) use ($t) {
                    $qq->where('nombre', 'like', $t)
                       ->orWhere('codigo_dane', 'like', $t)
                       ->orWhere('departamento', 'like', $t);
                });
            })
            ->orderBy('nombre')
            ->paginate($this->perPage);

        return view('livewire.municipios-nuevos.municipios', compact('rows'));
    }

   
}
