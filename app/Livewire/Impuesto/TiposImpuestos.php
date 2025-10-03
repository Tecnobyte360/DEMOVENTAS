<?php

namespace App\Livewire\Impuesto;

use Livewire\Component;
use Illuminate\Validation\Rule;
use App\Models\Impuesto\ImpuestoTipo;
use App\Models\Impuestos\Impuesto as ImpuestoModel; // para validar relaciones al borrar
use Throwable;

class TiposImpuestos extends Component
{
    // Filtros
    public string $q = '';
    public bool $soloActivos = true;

    // Modal / form
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $codigo = '';
    public string $nombre = '';
    public bool $es_retencion = false;
    public bool $activo = true;
    public int $orden = 1;

    protected $listeners = ['refrescar-tipos-impuesto' => '$refresh'];

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->resetForm();
        $this->editingId = $id;

        $t = ImpuestoTipo::findOrFail($id);
        $this->fill([
            'codigo'       => $t->codigo,
            'nombre'       => $t->nombre,
            'es_retencion' => (bool) $t->es_retencion,
            'activo'       => (bool) $t->activo,
            'orden'        => (int) $t->orden,
        ]);

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate($this->rules(), $this->messages());

        $payload = [
            'codigo'       => trim($this->codigo),
            'nombre'       => trim($this->nombre),
            'es_retencion' => $this->es_retencion,
            'activo'       => $this->activo,
            'orden'        => (int) $this->orden,
        ];

        ImpuestoTipo::updateOrCreate(['id' => $this->editingId], $payload);

        $this->dispatch('refrescar-tipos-impuesto');
        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActivo(int $id): void
    {
        $t = ImpuestoTipo::findOrFail($id);
        $t->activo = ! $t->activo;
        $t->save();
    }

    public function delete(int $id): void
    {
        try {
            // bloquear eliminación si hay impuestos usando este tipo
            $cnt = ImpuestoModel::where('tipo_id', $id)->count();
            if ($cnt > 0) {
                $this->addError('delete', 'No se puede eliminar: hay impuestos asociados.');
                return;
            }
            ImpuestoTipo::whereKey($id)->delete();
        } catch (Throwable $e) {
            $this->addError('delete', 'No fue posible eliminar este tipo.');
        }
    }

    protected function rules(): array
    {
        $unique = Rule::unique('impuesto_tipos', 'codigo');
        if ($this->editingId) $unique = $unique->ignore($this->editingId);

        return [
            'codigo'       => ['required','max:20', $unique],
            'nombre'       => ['required','max:255'],
            'es_retencion' => ['boolean'],
            'activo'       => ['boolean'],
            'orden'        => ['required','integer','min:1','max:65535'],
        ];
    }

    protected function messages(): array
    {
        return [
            'codigo.required' => 'Ingresa el código.',
            'codigo.unique'   => 'Ya existe un tipo con este código.',
            'nombre.required' => 'Ingresa el nombre.',
        ];
    }

    public function resetForm(): void
    {
        $this->reset(['editingId','codigo','nombre','es_retencion','activo','orden']);
        $this->es_retencion = false;
        $this->activo = true;
        $this->orden = 1;
    }

    public function render()
    {
        $items = ImpuestoTipo::query()
            ->when($this->q !== '', fn($q) => $q->where(fn($qq) =>
                $qq->where('codigo', 'like', "%{$this->q}%")
                   ->orWhere('nombre', 'like', "%{$this->q}%")
            ))
            ->when($this->soloActivos, fn($q) => $q->where('activo', true))
            ->orderBy('orden')->orderBy('nombre')
            ->get();

        return view('livewire.impuesto.tipos-impuestos', compact('items'));
    }
}
