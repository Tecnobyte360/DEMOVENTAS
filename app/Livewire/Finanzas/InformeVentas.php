<?php

namespace App\Livewire\Finanzas;

use App\Models\Factura\Factura;
use App\Models\Serie\Serie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InformeVentas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros
    public string $estadoFiltro   = 'todos';
    public string $tipoPagoFiltro = 'todos';
    public string $filtroCliente  = ''; // (aquí lo reutilizamos como Cliente/Proveedor)
    public ?string $fechaInicio   = null;
    public ?string $fechaFin      = null;
    public string $empresaFiltro  = 'todas';

    // ✅ SERIE (define si es ventas o compras)
    public ?int $serieId = null;
    public ?Serie $serieSeleccionada = null;

    // KPIs
    public int $totalFacturas    = 0;
    public float $totalContado   = 0;
    public float $totalCredito   = 0;
    public float $totalFacturado = 0;
    public float $totalPagado    = 0;
    public float $totalSaldo     = 0;

    // ✅ Ajusta estos códigos a los tuyos reales (tipos_documentos.codigo)
    protected array $codigosVentas  = ['FACTURA', 'NOTA_CREDITO'];
    protected array $codigosCompras = ['FACTURACOMPRA', 'NOTA_CREDITO_COMPRA', 'NOTACREDITOCOMPRA'];

    public function mount(): void
    {
        $this->fechaInicio = now()->startOfMonth()->toDateString();
        $this->fechaFin    = now()->toDateString();
    }

    public function updated($property): void
    {
        $this->resetPage();

        if ($property === 'serieId') {
            $this->serieSeleccionada = $this->serieId
                ? Serie::with('tipo')->find($this->serieId)
                : null;
        }
    }

    public function cargarVentas(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->estadoFiltro   = 'todos';
        $this->tipoPagoFiltro = 'todos';
        $this->empresaFiltro  = 'todas';
        $this->filtroCliente  = '';
        $this->fechaInicio    = now()->startOfMonth()->toDateString();
        $this->fechaFin       = now()->toDateString();

        $this->serieId = null;
        $this->serieSeleccionada = null;

        $this->resetPage();
    }

    /** ✅ Determina si el informe actual es COMPRAS según la serie seleccionada */
    protected function esCompra(): bool
    {
        $codigo = strtoupper($this->serieSeleccionada?->tipo?->codigo ?? '');

        // Si no hay serie seleccionada => por defecto es VENTAS
        if ($codigo === '') return false;

        $comprasCodigos = array_map('strtoupper', $this->codigosCompras);
        return in_array($codigo, $comprasCodigos, true);
    }

    protected function baseQuery(): Builder
    {
        // ✅ Si tienes proveedor(), inclúyelo para no romper la vista en compras
        $q = Factura::query()->with(['cliente', 'proveedor', 'empresa', 'serie.tipo']);

        // ✅ SERIE define el tipo (ventas/compras)
        if ($this->serieId) {
            $q->where('serie_id', $this->serieId);

            if (!$this->serieSeleccionada || $this->serieSeleccionada->id !== $this->serieId) {
                $this->serieSeleccionada = Serie::with('tipo')->find($this->serieId);
            }
        } else {
            // ✅ Por defecto SOLO VENTAS
            $codigos = array_map('strtoupper', $this->codigosVentas);

            $q->whereHas('serie.tipo', function ($t) use ($codigos) {
                $t->whereIn(DB::raw('UPPER(codigo)'), $codigos);
            });
        }

        // Estado
        if ($this->estadoFiltro !== 'todos') {
            if ($this->estadoFiltro === 'vencida') {
                $q->where('saldo', '>', 0)
                    ->whereNotNull('vencimiento')
                    ->whereDate('vencimiento', '<', now()->toDateString());
            } else {
                $q->where('estado', $this->estadoFiltro);
            }
        }

        // Tipo pago
        if ($this->tipoPagoFiltro !== 'todos') {
            $q->where('tipo_pago', $this->tipoPagoFiltro);
        }

        // Empresa
        if ($this->empresaFiltro !== 'todas' && $this->empresaFiltro !== '') {
            $q->where('empresa_id', $this->empresaFiltro);
        }

        // ✅ Cliente/Proveedor según si es compra
        if (trim($this->filtroCliente) !== '') {
            $f = trim($this->filtroCliente);

            if ($this->esCompra()) {
                $q->whereHas('proveedor', function ($qq) use ($f) {
                    $qq->where('razon_social', 'like', "%{$f}%");
                });
            } else {
                $q->whereHas('cliente', function ($qq) use ($f) {
                    $qq->where('razon_social', 'like', "%{$f}%");
                });
            }
        }

        // Rango de fechas
        if ($this->fechaInicio && $this->fechaFin) {
            $q->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin]);
        } elseif ($this->fechaInicio) {
            $q->whereDate('fecha', '>=', $this->fechaInicio);
        } elseif ($this->fechaFin) {
            $q->whereDate('fecha', '<=', $this->fechaFin);
        }

        return $q;
    }

    protected function calcularKpis(Builder $query): void
    {
        $base = clone $query;

        $this->totalFacturas  = (int) $base->count();
        $this->totalFacturado = (float) $base->sum('total');
        $this->totalPagado    = (float) $base->sum('pagado');
        $this->totalSaldo     = (float) $base->sum('saldo');

        $this->totalContado = (float) (clone $query)->where('tipo_pago', 'contado')->sum('total');
        $this->totalCredito = (float) (clone $query)->where('tipo_pago', 'credito')->sum('total');
    }

   public function render()
{
    // ✅ Trae series activas y las separa según codigo del tipo de documento
    $series = Serie::query()
        ->with('tipo')
        ->activa()
        ->orderBy('nombre')
        ->get();

    $ventasCodigos  = array_map('strtoupper', $this->codigosVentas);
    $comprasCodigos = array_map('strtoupper', $this->codigosCompras);

    $seriesVentas  = $series->filter(fn($s) => in_array(strtoupper($s->tipo->codigo ?? ''), $ventasCodigos, true));
    $seriesCompras = $series->filter(fn($s) => in_array(strtoupper($s->tipo->codigo ?? ''), $comprasCodigos, true));

    $query = $this->baseQuery();

    $facturas = $query
        ->orderByDesc('fecha')
        ->orderByDesc('id')
        ->paginate(15);

    $this->calcularKpis($query);

    // ✅ Determina si es compras (según la serie seleccionada)
    $esCompra = $this->esCompra();

    // ✅ Lista sugerida de terceros (datalist) basada en los resultados actuales
   $terceros = $facturas->getCollection()
    ->map(function ($f) use ($esCompra) {
        return $esCompra
            ? optional($f->proveedor)->razon_social
            : optional($f->cliente)->razon_social;
    })
    ->filter()
    ->unique()
    ->values()
    ->take(50)
    ->toArray();


    return view('livewire.finanzas.informe-ventas', [
        'facturas'          => $facturas,
        'seriesVentas'      => $seriesVentas,
        'seriesCompras'     => $seriesCompras,
        'serieSeleccionada' => $this->serieSeleccionada,
        'esCompra'          => $esCompra,
        'terceros'          => $terceros,
    ]);
}

}
