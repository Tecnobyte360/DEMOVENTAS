<?php

namespace App\Livewire\Bodegas;

use Livewire\Attributes\On;
use Livewire\Component;
// Usa alias para no chocar con el nombre del componente:
use App\Models\Bodega as BodegaModel;

class Bodega extends Component
{
    // Lista
    public array $bodegas = [];

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
        $this->bodegas = BodegaModel::orderBy('id', 'desc')->get()->toArray();
    }

    public function abrirCrear(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function abrirEditar(int $id): void
    {
        $m = BodegaModel::findOrFail($id);

        $this->bodega_id = $m->id;
        $this->nombre    = (string) $m->nombre;
        $this->ubicacion = (string) $m->ubicacion;
        $this->activo    = (bool) $m->activo;

        $this->showModal = true;
    }

    public function guardar(): void
    {
        $this->validate();

        BodegaModel::updateOrCreate(
            ['id' => $this->bodega_id],
            [
                'nombre'    => $this->nombre,
                'ubicacion' => $this->ubicacion,
                'activo'    => $this->activo,
            ]
        );

        $this->dispatch('toast', type: 'success', message: 'Bodega guardada correctamente.');
        $this->dispatch('listarBodegas'); // refresca lista (también llama a listar())

        $this->showModal = false;
        $this->resetForm();
        $this->listar(); // asegura refresco si no se captura el evento
    }

    public function toggleEstado(int $id): void
    {
        $m = BodegaModel::findOrFail($id);
        $m->activo = ! $m->activo;
        $m->save();

        $this->dispatch('toast', type: 'info', message: $m->activo ? 'Bodega activada.' : 'Bodega desactivada.');
        $this->listar();
    }

    public function eliminar(int $id): void
    {
        BodegaModel::whereKey($id)->delete();
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
