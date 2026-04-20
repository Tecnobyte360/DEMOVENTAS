<?php

namespace App\Livewire\Finanzas;

use App\Models\Factura\Factura;
use App\Models\Serie\Serie;
use App\Models\User;
use App\Models\cotizaciones\cotizacione as CotizacionModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InformeVentas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros
    public string $estadoFiltro        = 'todos';
    public string $tipoPagoFiltro      = 'todos';
    public string $tipoDocumentoFiltro = 'todos'; // FACTURA | COTIZACION | NOTA_CREDITO | todos
    public string $filtroCliente       = '';
    public string $empresaFiltro       = 'todas';
    public array $asesoresFiltro       = []; // IDs de asesores seleccionados; vacío = todos

    public ?string $fechaInicio = null;
    public ?string $fechaFin    = null;

    // Serie seleccionada
    public ?int $serieId = null;
    public ?Serie $serieSeleccionada = null;

    // KPIs
    public int $totalFacturas      = 0;
    public float $totalContado     = 0;
    public float $totalCredito     = 0;
    public float $totalFacturado   = 0;
    public float $totalPagado      = 0;
    public float $totalSaldo       = 0;

    // KPIs de rentabilidad (solo ventas: no compras, no cotizaciones)
    public float $totalBaseVenta   = 0; // importe_base (sin impuestos)
    public float $totalCostoVenta  = 0;
    public float $totalGanancia    = 0;
    public float $margenPromedio   = 0; // %

    protected array $codigosVentas = [
        'FACTURA',
        'NOTA_CREDITO',
        'COTIZACION',
    ];

    protected array $codigosCompras = [
        'FACTURACOMPRA',
        'NOTA_CREDITO_COMPRA',
        'NOTACREDITOCOMPRA',
    ];

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
        $this->estadoFiltro        = 'todos';
        $this->tipoPagoFiltro      = 'todos';
        $this->tipoDocumentoFiltro = 'todos';
        $this->empresaFiltro       = 'todas';
        $this->asesoresFiltro      = [];
        $this->filtroCliente       = '';

        $this->fechaInicio = now()->startOfMonth()->toDateString();
        $this->fechaFin    = now()->toDateString();

        $this->serieId = null;
        $this->serieSeleccionada = null;

        $this->resetPage();
    }

    protected function esCompra(): bool
    {
        $codigo = strtoupper($this->serieSeleccionada?->tipo?->codigo ?? '');

        if ($codigo === '') {
            return false;
        }

        return in_array($codigo, array_map('strtoupper', $this->codigosCompras), true);
    }

    protected function esCotizacion(): bool
    {
        return strtoupper($this->tipoDocumentoFiltro) === 'COTIZACION';
    }

    protected function baseQuery(): Builder
    {
        $esCompra     = $this->esCompra();
        $esCotizacion = $this->esCotizacion();

        /*
        |--------------------------------------------------------------------------
        | COTIZACIONES
        |--------------------------------------------------------------------------
        */
        if ($esCotizacion) {
            $q = CotizacionModel::query()->with([
                'cliente',
                'creadoPor:id,name',
            ]);

            // Estado
            if ($this->estadoFiltro !== 'todos') {
                $q->where('estado', $this->estadoFiltro);
            }

            // Asesor (multi-select)
            $asesoresIds = array_filter(array_map('intval', $this->asesoresFiltro ?? []));
            if (!empty($asesoresIds)) {
                $q->whereIn('creado_por_id', $asesoresIds);
            }

            // Cliente
            if (trim($this->filtroCliente) !== '') {
                $f = trim($this->filtroCliente);

                $q->whereHas('cliente', function ($qq) use ($f) {
                    $qq->where('razon_social', 'like', "%{$f}%")
                        ->orWhere('nit', 'like', "%{$f}%");
                });
            }

            // Fechas
            if ($this->fechaInicio && $this->fechaFin) {
                $q->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin]);
            } elseif ($this->fechaInicio) {
                $q->whereDate('fecha', '>=', $this->fechaInicio);
            } elseif ($this->fechaFin) {
                $q->whereDate('fecha', '<=', $this->fechaFin);
            }

            return $q;
        }

        /*
        |--------------------------------------------------------------------------
        | FACTURAS / NOTAS / COMPRAS
        |--------------------------------------------------------------------------
        */
        $with = [
            'empresa',
            'serie.tipo',
            'creadoPor:id,name',
        ];

        if ($esCompra) {
            $with[] = 'socioNegocio';
        } else {
            $with[] = 'cliente';
        }

        $q = Factura::query()->with($with);

        if ($this->serieId) {
            $q->where('serie_id', $this->serieId);

            if (!$this->serieSeleccionada || $this->serieSeleccionada->id !== $this->serieId) {
                $this->serieSeleccionada = Serie::with('tipo')->find($this->serieId);
            }
        } else {
            $codigos = array_map('strtoupper', $this->codigosVentas);

            $q->whereHas('serie.tipo', function ($t) use ($codigos) {
                $t->whereIn(DB::raw('UPPER(codigo)'), $codigos);
            });
        }

        if ($this->tipoDocumentoFiltro !== 'todos') {
            $tipoBuscado = strtoupper($this->tipoDocumentoFiltro);

            $q->whereHas('serie.tipo', function ($t) use ($tipoBuscado) {
                $t->whereRaw('UPPER(codigo) = ?', [$tipoBuscado]);
            });
        }

        if ($this->estadoFiltro !== 'todos') {
            if ($this->estadoFiltro === 'vencida') {
                $q->where('saldo', '>', 0)
                    ->whereNotNull('vencimiento')
                    ->whereDate('vencimiento', '<', now()->toDateString());
            } else {
                $q->where('estado', $this->estadoFiltro);
            }
        }

        if ($this->tipoPagoFiltro !== 'todos') {
            $q->where('tipo_pago', $this->tipoPagoFiltro);
        }

        if ($this->empresaFiltro !== 'todas' && $this->empresaFiltro !== '') {
            $q->where('empresa_id', $this->empresaFiltro);
        }

        $asesoresIds = array_filter(array_map('intval', $this->asesoresFiltro ?? []));
        if (!empty($asesoresIds)) {
            $q->whereIn('creado_por_id', $asesoresIds);
        }

        if (trim($this->filtroCliente) !== '') {
            $f = trim($this->filtroCliente);

            if ($esCompra) {
                $q->whereHas('socioNegocio', function ($qq) use ($f) {
                    $qq->where('razon_social', 'like', "%{$f}%")
                        ->orWhere('nit', 'like', "%{$f}%");
                });
            } else {
                $q->whereHas('cliente', function ($qq) use ($f) {
                    $qq->where('razon_social', 'like', "%{$f}%")
                        ->orWhere('nit', 'like', "%{$f}%");
                });
            }
        }

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

        if ($this->esCotizacion()) {
            $this->totalPagado  = 0;
            $this->totalSaldo   = (float) $base->sum('total');
            $this->totalContado = 0;
            $this->totalCredito = 0;
            return;
        }

        $this->totalPagado = (float) $base->sum('pagado');
        $this->totalSaldo  = (float) $base->sum('saldo');

        $this->totalContado = (float) (clone $query)
            ->where('tipo_pago', 'contado')
            ->sum('total');

        $this->totalCredito = (float) (clone $query)
            ->where('tipo_pago', 'credito')
            ->sum('total');
    }

    /**
     * Calcula costo y utilidad agregados para el total filtrado (solo ventas).
     * Usa costo_promedio -> ultimo_costo -> producto.costo como fallback.
     */
    protected function calcularRentabilidadTotal(Builder $query): void
    {
        $this->totalBaseVenta  = 0;
        $this->totalCostoVenta = 0;
        $this->totalGanancia   = 0;
        $this->margenPromedio  = 0;

        if ($this->esCompra() || $this->esCotizacion()) {
            return;
        }

        $ids = (clone $query)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        $row = DB::table('factura_detalles as fd')
            ->leftJoin('producto_bodega as pb', function ($j) {
                $j->on('pb.producto_id', '=', 'fd.producto_id')
                  ->on('pb.bodega_id', '=', 'fd.bodega_id');
            })
            ->leftJoin('productos as p', 'p.id', '=', 'fd.producto_id')
            ->whereIn('fd.factura_id', $ids)
            ->selectRaw('
                COALESCE(SUM(fd.importe_base), 0) AS venta,
                COALESCE(SUM(
                    fd.cantidad * COALESCE(
                        NULLIF(pb.costo_promedio, 0),
                        NULLIF(pb.ultimo_costo, 0),
                        p.costo,
                        0
                    )
                ), 0) AS costo
            ')
            ->first();

        $venta = (float) ($row->venta ?? 0);
        $costo = (float) ($row->costo ?? 0);

        $this->totalBaseVenta  = round($venta, 2);
        $this->totalCostoVenta = round($costo, 2);
        $this->totalGanancia   = round($venta - $costo, 2);
        $this->margenPromedio  = $venta > 0 ? round((($venta - $costo) / $venta) * 100, 2) : 0;
    }

    /**
     * Calcula rentabilidad por factura para la página actual.
     * Devuelve array keyed por factura_id con claves: costo, ganancia, margen.
     */
    protected function rentabilidadPorFactura(Collection $items): array
    {
        if ($this->esCompra() || $this->esCotizacion() || $items->isEmpty()) {
            return [];
        }

        $ids = $items->pluck('id');

        $rows = DB::table('factura_detalles as fd')
            ->leftJoin('producto_bodega as pb', function ($j) {
                $j->on('pb.producto_id', '=', 'fd.producto_id')
                  ->on('pb.bodega_id', '=', 'fd.bodega_id');
            })
            ->leftJoin('productos as p', 'p.id', '=', 'fd.producto_id')
            ->whereIn('fd.factura_id', $ids)
            ->groupBy('fd.factura_id')
            ->selectRaw('
                fd.factura_id,
                COALESCE(SUM(fd.importe_base), 0) AS venta,
                COALESCE(SUM(
                    fd.cantidad * COALESCE(
                        NULLIF(pb.costo_promedio, 0),
                        NULLIF(pb.ultimo_costo, 0),
                        p.costo,
                        0
                    )
                ), 0) AS costo
            ')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $venta = (float) $r->venta;
            $costo = (float) $r->costo;
            $gan   = $venta - $costo;
            $map[(int) $r->factura_id] = [
                'venta'    => round($venta, 2),
                'costo'    => round($costo, 2),
                'ganancia' => round($gan, 2),
                'margen'   => $venta > 0 ? round(($gan / $venta) * 100, 2) : 0,
            ];
        }

        return $map;
    }

    protected function obtenerTerceros(Collection $items, bool $esCompra, bool $esCotizacion): array
    {
        return $items->map(function ($f) use ($esCompra, $esCotizacion) {
            if ($esCotizacion) {
                return optional($f->cliente)->razon_social;
            }

            return $esCompra
                ? optional($f->socioNegocio)->razon_social
                : optional($f->cliente)->razon_social;
        })
        ->filter()
        ->unique()
        ->values()
        ->take(50)
        ->toArray();
    }

    public function render()
    {
        $series = Serie::query()
            ->with('tipo')
            ->activa()
            ->orderBy('nombre')
            ->get();

        $ventasCodigos  = array_map('strtoupper', $this->codigosVentas);
        $comprasCodigos = array_map('strtoupper', $this->codigosCompras);

        $seriesVentas = $series->filter(
            fn ($s) => in_array(strtoupper($s->tipo->codigo ?? ''), $ventasCodigos, true)
        );

        $seriesCompras = $series->filter(
            fn ($s) => in_array(strtoupper($s->tipo->codigo ?? ''), $comprasCodigos, true)
        );

        $query = $this->baseQuery();

        $items = $query
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15);

        $this->calcularKpis(clone $query);
        $this->calcularRentabilidadTotal(clone $query);

        $esCompra     = $this->esCompra();
        $esCotizacion = $this->esCotizacion();

        $terceros = $this->obtenerTerceros($items->getCollection(), $esCompra, $esCotizacion);

        $rentabilidad = $this->rentabilidadPorFactura($items->getCollection());

        $asesores = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.finanzas.informe-ventas', [
            'facturas'          => $items,
            'seriesVentas'      => $seriesVentas,
            'seriesCompras'     => $seriesCompras,
            'serieSeleccionada' => $this->serieSeleccionada,
            'esCompra'          => $esCompra,
            'esCotizacion'      => $esCotizacion,
            'terceros'          => $terceros,
            'asesores'          => $asesores,
            'rentabilidad'      => $rentabilidad,
        ]);
    }
}