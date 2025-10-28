<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Inventario\EntradaMercancia;
use App\Models\Inventario\EntradaDetalle;
use Illuminate\Support\Carbon;

class ListaEntradasGeneradas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros
    public ?string $filtro_desde = null; 
    public ?string $filtro_hasta = null; 
    public bool $filtroAplicado = false;

    // Fila expandible
    public ?int $filaAbiertaId = null;
    /** @var array<int, \Illuminate\Support\Collection> */
    public array $detallesPorEntrada = [];

    public function render()
    {
        $desde = $this->filtro_desde ? Carbon::parse($this->filtro_desde)->startOfDay() : null;
        $hasta = $this->filtro_hasta ? Carbon::parse($this->filtro_hasta)->endOfDay()   : null;

        if ($desde && $hasta && $hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        $entradas = EntradaMercancia::with('socioNegocio')
            ->when($desde && $hasta, fn($q) => $q->whereBetween('fecha_contabilizacion', [$desde, $hasta]))
            ->when($desde && !$hasta, fn($q) => $q->where('fecha_contabilizacion', '>=', $desde))
            ->when(!$desde && $hasta, fn($q) => $q->where('fecha_contabilizacion', '<=', $hasta))
            ->orderByDesc('fecha_contabilizacion')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.inventario.lista-entradas-generadas', [
            'entradas' => $entradas,
        ]);
    }

    public function aplicarFiltros(): void
    {
        $this->filtroAplicado = true;
        $this->resetPage();
        $this->cerrarFila();
    }

    public function limpiarFiltros(): void
    {
        $this->filtro_desde = null;
        $this->filtro_hasta = null;
        $this->filtroAplicado = false;
        $this->resetPage();
        $this->cerrarFila();
    }

    public function toggleDetalleFila(int $entradaId): void
    {
        if ($this->filaAbiertaId === $entradaId) {
            $this->filaAbiertaId = null;
            return;
        }

        $this->filaAbiertaId = $entradaId;

        if (!isset($this->detallesPorEntrada[$entradaId])) {
            $this->detallesPorEntrada[$entradaId] =
                EntradaDetalle::with(['producto', 'bodega'])
                    ->where('entrada_mercancia_id', $entradaId)
                    ->get();
        }
    }

    public function cerrarFila(): void
    {
        $this->filaAbiertaId = null;
    }
}
