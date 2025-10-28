<?php

namespace App\Livewire\Bodegas;

use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Throwable;

use App\Models\Bodega; // ✅ único import del modelo

class Create extends Component
{
    public ?int $bodega_id = null;

    public string $nombre = '';
    public string $ubicacion = '';
    public bool   $activo = true;

    public string $mensaje = '';
    public string $tipoMensaje = 'success';

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:120',
                // especifica la PK para evitar ambigüedad
                Rule::unique('bodegas','nombre')->ignore($this->bodega_id, 'id'),
            ],
            'ubicacion' => ['required', 'string', 'max:255'],
            'activo'    => ['boolean'],
        ];
    }

    protected array $messages = [
        'nombre.required'    => 'El nombre de la bodega es obligatorio.',
        'nombre.unique'      => 'Ya existe una bodega con ese nombre.',
        'ubicacion.required' => 'La ubicación es obligatoria.',
    ];

    public function mount(?int $bodegaId = null): void
    {
        if ($bodegaId) {
            $this->bodega_id = $bodegaId;
            $this->cargarBodega($bodegaId);
        }
    }

    public function cargarBodega(int $id): void
    {
        if ($b = Bodega::find($id)) {
            $this->nombre    = (string) $b->nombre;
            $this->ubicacion = (string) $b->ubicacion;
            $this->activo    = (bool) $b->activo;
        }
    }

    public function updated($name): void
    {
        $this->validateOnly($name);
    }

    public function guardar(): void
    {
        $this->nombre    = trim($this->nombre);
        $this->ubicacion = trim($this->ubicacion);

        $data = $this->validate();

        try {
            if ($this->bodega_id) {
                $bodega = Bodega::findOrFail($this->bodega_id);
                $bodega->update($data);
                $this->mensaje = '✅ Bodega actualizada exitosamente.';
                $this->dispatch('bodegaActualizada', id: $bodega->id);
            } else {
                $bodega = Bodega::create($data);
                $this->mensaje = '✅ Bodega creada exitosamente.';
                $this->dispatch('bodegaCreada', id: $bodega->id);
            }

            $this->tipoMensaje = 'success';
            $this->dispatch('toast', type: 'success', message: $this->mensaje);

            $this->resetForm();
            $this->dispatch('cerrarModal');
            $this->dispatchBrowserEvent('cerrar-modal-bodega');

        } catch (QueryException $e) {
            $this->mensaje = '❌ Error en la base de datos.';
            $this->tipoMensaje = 'error';
            $this->dispatch('toast', type: 'error', message: $this->mensaje);
        } catch (Throwable $e) {
            $this->mensaje = '⚠ Ocurrió un error inesperado.';
            $this->tipoMensaje = 'warning';
            $this->dispatch('toast', type: 'warning', message: $this->mensaje);
        }
    }

    public function cancelar(): void
    {
        $this->resetForm();
        $this->dispatch('cerrarModal');
    }

    private function resetForm(): void
    {
        $this->reset(['bodega_id', 'nombre', 'ubicacion', 'activo']);
        $this->activo = true;
    }

    public function render()
    {
        return view('livewire.bodegas.create');
    }
}
