<?php

namespace App\Livewire\Bodegas;

use App\Models\bodegas;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Validation\Rule;

class Bodega extends Component
{
    // Lista
    public $bodegas = [];

    // Formulario
    public ?int $bodega_id = null;
    public string $nombre = '';
    public string $ubicacion = '';
    public bool $activo = true;

    // UI
    public bool $showModal = false;

    protected function rules(): array
    {
        return [
            'nombre'    => ['required', 'string', 'max:255'],
            'ubicacion' => ['required', 'string', 'max:255'],
            'activo'    => ['boolean'],
        ];
    }

    protected array $messages = [
        'nombre.required'    => 'El nombre es obligatorio.',
        'ubicacion.required' => 'La ubicación es obligatoria.',
    ];

    public function mount(): void
    {
        $this->listar();
    }

    #[On('listarBodegas')]
    public function listar(): void
    {
        $this->bodegas = bodegas::orderBy('id','desc')->get()->toArray();
    }

    public function abrirCrear(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function abrirEditar(int $id): void
    {
        $m = bodegas::findOrFail($id);

        $this->bodega_id = $m->id;
        $this->nombre    = (string) $m->nombre;
        $this->ubicacion = (string) $m->ubicacion;
        $this->activo    = (bool) $m->activo;

        $this->showModal = true;
    }

    public function guardar(): void
    {
        $this->validate();

        bodegas::updateOrCreate(
            ['id' => $this->bodega_id],
            [
                'nombre'    => $this->nombre,
                'ubicacion' => $this->ubicacion,
                'activo'    => $this->activo,
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Bodega guardada correctamente.');
        $this->dispatch('listarBodegas'); // refresca lista (también llama a listar() en este mismo comp)

        $this->showModal = false;
        $this->resetForm();
        $this->listar(); // por si no usas el evento arriba, asegura refresco
    }

    public function toggleEstado(int $id): void
    {
        $m = bodegas::findOrFail($id);
        $m->activo = ! $m->activo;
        $m->save();

        $this->dispatch('toast', type: 'info', message: $m->activo ? 'Bodega activada.' : 'Bodega desactivada.');
        $this->listar();
    }

    public function eliminar(int $id): void
    {
        bodegas::whereKey($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Bodega eliminada.');
        $this->listar();
    }

    public function resetForm(): void
    {
        $this->bodega_id = null;
        $this->nombre = '';
        $this->ubicacion = '';
        $this->activo = true;
    }

    public function render()
    {
        return view('livewire.bodegas.bodega');
    }
}
