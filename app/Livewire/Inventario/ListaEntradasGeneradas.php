<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Inventario\EntradaMercancia;
use App\Models\Inventario\EntradaDetalle;
use App\Models\Serie\Serie;
use App\Services\EntradaMercanciaService;
use Masmerise\Toaster\PendingToast;

class ListaEntradasGeneradas extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    /** ===== Filtros ===== */
    public ?string $filtro_desde = null; // YYYY-MM-DD
    public ?string $filtro_hasta = null; // YYYY-MM-DD
    public bool    $filtroAplicado = false;

    /** ===== Fila expandible (detalle inline) ===== */
    public ?int $filaAbiertaId = null;
    /** @var array<int, \Illuminate\Support\Collection> */
    public array $detallesPorEntrada = [];

    /** ===== Series para formato del número ===== */
    public $series; // \Illuminate\Support\Collection

    /** Código del documento que lista (por si lo reutilizas) */
    public string $documento = 'ENTRADA_MERCANCIA';

    /* ==========================
     | Ciclo de vida
     ========================== */
    public function mount(): void
    {
        // Normaliza fechas iniciales opcionalmente
        // $this->filtro_desde = now()->startOfMonth()->toDateString();
        // $this->filtro_hasta = now()->toDateString();

        // Series activas del tipo de documento (para mostrar longitud/prefijo)
        $this->series = Serie::query()
            ->with('tipo')
            ->activa()
            ->when(
                $this->documento !== '',
                fn($q) => $q->whereHas('tipo', fn($t) => $t->where('codigo', $this->documento))
            )
            ->orderBy('nombre')
            ->get(['id','nombre','prefijo','desde','hasta','proximo','longitud','es_default','tipo_documento_id']);
    }

    /* ==========================
     | Filtros
     ========================== */
    public function buscar(): void
    {
        $this->aplicarFiltros();
        $this->cerrarFila();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['filtro_desde','filtro_hasta','filtroAplicado']);
        $this->resetPage();
    }

    public function aplicarFiltros(): void
    {
        $this->filtroAplicado = true;
        $this->resetPage();
    }

    /* ==========================
     | Fila expandible
     ========================== */
    public function cerrarFila(): void
    {
        $this->filaAbiertaId = null;
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

    /* ==========================
     | Acciones Emitir/Revertir
     ========================== */
    public function emitirEntrada(int $entradaId): void
    {
        try {
            $e = EntradaMercancia::with('detalles.producto.bodegas')->findOrFail($entradaId);

            if ($e->estado === 'emitida') {
                PendingToast::create()->info()->message('Esta entrada ya está emitida.')->duration(4000);
                return;
            }

            // Asegurar serie default del documento
            $serieDefault = Serie::defaultParaCodigo($this->documento);
            if (!$serieDefault) {
                throw new \RuntimeException('No hay serie default activa para ENTRADA_MERCANCIA.');
            }

            DB::transaction(function () use ($e, $serieDefault) {
                // Numeración
                $n = $serieDefault->tomarConsecutivo();

                $e->update([
                    'serie_id' => $serieDefault->id,
                    'prefijo'  => $serieDefault->prefijo,
                    'numero'   => $n,
                    'estado'   => 'emitida',
                ]);

                // Movimiento de inventario y kardex
                EntradaMercanciaService::emitir($e);

                // Recalcular stock global del producto (opcional)
                foreach ($e->detalles as $d) {
                    $total = $d->producto->bodegas()->sum('producto_bodega.stock');
                    $d->producto->update(['stock' => $total]);
                }
            }, 3);

            PendingToast::create()->success()->message('Entrada emitida y Kardex actualizado.')->duration(5000);
            $this->resetPage();
        } catch (\Throwable $ex) {
            PendingToast::create()->error()->message('No se pudo emitir: '.$ex->getMessage())->duration(9000);
        }
    }

    public function revertirEntrada(int $entradaId): void
    {
        try {
            $e = EntradaMercancia::with('detalles.producto.bodegas')->findOrFail($entradaId);

            if ($e->estado !== 'emitida') {
                PendingToast::create()->info()->message('Solo puedes revertir entradas emitidas.')->duration(5000);
                return;
            }

            EntradaMercanciaService::revertir($e);

            foreach ($e->detalles as $d) {
                $total = $d->producto->bodegas()->sum('producto_bodega.stock');
                $d->producto->update(['stock' => $total]);
            }

            PendingToast::create()->success()->message('Entrada revertida correctamente.')->duration(5000);
            $this->resetPage();
        } catch (\Throwable $ex) {
            PendingToast::create()->error()->message('No se pudo revertir: '.$ex->getMessage())->duration(9000);
        }
    }

    /* ==========================
     | Render
     ========================== */
    public function render()
    {
        // Normaliza rango (y corrige inversión)
        $desde = $this->filtro_desde ? Carbon::parse($this->filtro_desde)->startOfDay() : null;
        $hasta = $this->filtro_hasta ? Carbon::parse($this->filtro_hasta)->endOfDay()   : null;
        if ($desde && $hasta && $hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        $entradasPaginadas = EntradaMercancia::with('socioNegocio')
            ->when($desde && $hasta, fn($q) => $q->whereBetween('fecha_contabilizacion', [$desde, $hasta]))
            ->when($desde && !$hasta, fn($q) => $q->where('fecha_contabilizacion', '>=', $desde))
            ->when(!$desde && $hasta, fn($q) => $q->where('fecha_contabilizacion', '<=', $hasta))
            ->orderByDesc('fecha_contabilizacion')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.inventario.lista-entradas-generadas', [
            'entradasMercancia' => $entradasPaginadas,
            'series'            => $this->series,
        ]);
    }
}
