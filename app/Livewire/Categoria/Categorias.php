<?php

namespace App\Livewire\Categoria;

use App\Models\Categorias\Categoria;
use Livewire\Component;
use Masmerise\Toaster\PendingToast;
use Illuminate\Support\Facades\Log;

class Categorias extends Component
{
    public $categorias;
    public $nombre;
    public $descripcion;
    public $activo = true;
    public $categoria_id;
    public $isEdit = false;

    public function render()
    {
        $this->categorias = Categoria::all();
        return view('livewire.categoria.categorias');
    }

    public function store()
    {
        try {
            $this->validate([
                'nombre' => 'required'
            ]);

            Categoria::create([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
            ]);

            $this->resetInput();

            PendingToast::create()
                ->success()
                ->message('La categoría fue registrada correctamente')
                ->duration(5000);
        } catch (\Throwable $e) {
            Log::error('Error al registrar categoría', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al registrar la categoría: ' . $e->getMessage())
                ->duration(9000);
        }
    }

    public function edit($id)
    {
        try {
            $categoria = Categoria::findOrFail($id);
            $this->fill($categoria->toArray());
            $this->categoria_id = $id;
            $this->isEdit = true;
        } catch (\Throwable $e) {
            Log::error('Error al cargar categoría', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al cargar la categoría: ' . $e->getMessage())
                ->duration(9000);
        }
    }

    public function update()
    {
        try {
            $this->validate([
                'nombre' => 'required'
            ]);

            Categoria::findOrFail($this->categoria_id)->update([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
            ]);

            $this->resetInput();

            PendingToast::create()
                ->success()
                ->message('La categoría fue actualizada correctamente')
                ->duration(5000);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar categoría', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al actualizar la categoría: ' . $e->getMessage())
                ->duration(9000);
        }
    }

    #[\Livewire\Attributes\On('eliminarCategoria')]
    public function delete($id)
    {
        try {
            Categoria::destroy($id);

            PendingToast::create()
                ->success()
                ->message('La categoría fue eliminada correctamente')
                ->duration(5000);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar categoría', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al eliminar la categoría: ' . $e->getMessage())
                ->duration(9000);
        }
    }

    private function resetInput()
    {
        $this->reset([
            'nombre',
            'descripcion',
            'activo',
            'categoria_id',
            'isEdit',
        ]);
    }
}
