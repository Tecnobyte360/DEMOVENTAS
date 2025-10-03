<?php

namespace App\Livewire\Finanzas;

use Livewire\Component;
use Illuminate\Support\Carbon;

use App\Models\Pedidos\PedidoDetalle;
use App\Models\SocioNegocio\SocioNegocio as Socio;
use App\Models\Productos\Producto as Producto;
use App\Models\Inventario\EntradaMercancia; // ← para Entradas

class Reportes extends Component
{
    public string $desde;
    public string $hasta;

    // Filtros
    public ?int $clienteId = null;
    public ?int $proveedorId = null; // reservado para CxP a futuro
    public array $clientesOptions = []; // selector por nombre

    // Resultados ventas
    public array $topClientes = [];
    public array $topProductos = [];
    public array $comprasPorCliente = [];

    // Entradas de mercancía por socio
    public array $entradasSocio = [];
    public float $entradasSocioTotal = 0.0;

    // CxP (desactivado por ahora)
    public array $cxpResumen = [];
    public array $cxpFacturas = [];

    public function mount(): void
    {
        $this->desde = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->hasta = Carbon::now()->format('Y-m-d');

        // Carga rápida de opciones de clientes (ajusta límite si necesitas)
        $this->clientesOptions = Socio::orderBy('razon_social')
            ->limit(300)
            ->get(['id','razon_social'])
            ->map(fn($c) => ['id'=>$c->id, 'nombre'=>$c->razon_social])
            ->toArray();

        $this->generar();
    }

    public function updated($prop): void
    {
        if (in_array($prop, ['desde', 'hasta', 'clienteId'], true)) {
            $this->generar();
        }
    }

    public function limpiarCliente(): void
    {
        $this->clienteId = null;
        $this->generar();
    }

    public function generar(): void
    {
        // Normaliza por si invierten fechas
        if (Carbon::parse($this->desde)->gt(Carbon::parse($this->hasta))) {
            [$this->desde, $this->hasta] = [$this->hasta, $this->desde];
        }

        $this->calcularTopClientes();
        $this->calcularTopProductos();
        $this->calcularComprasPorCliente();
        $this->calcularEntradasPorSocio(); // ← NUEVO

        // Cuando tengas CxP:
        // $this->calcularCuentasPorPagar();
    }

    /**
     * 1) TOP CLIENTES por ventas del periodo (detalle del pedido).
     *   SUM(cantidad * precio_unitario)
     */
    protected function calcularTopClientes(): void
    {
        $rows = PedidoDetalle::query()
            ->join('pedidos', 'pedidos.id', '=', 'pedido_detalles.pedido_id')
            ->where('pedidos.estado', '!=', 'cancelado')
            ->whereBetween('pedidos.fecha', [$this->desde, $this->hasta])
            ->when($this->clienteId, fn($q) => $q->where('pedidos.socio_negocio_id', $this->clienteId))
            ->selectRaw("
                pedidos.socio_negocio_id,
                SUM(pedido_detalles.cantidad * pedido_detalles.precio_unitario) AS total
            ")
            ->groupBy('pedidos.socio_negocio_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $clientes = Socio::whereIn('id', $rows->pluck('socio_negocio_id'))->pluck('razon_social', 'id');

        $this->topClientes = $rows->map(fn($r) => [
            'cliente' => $clientes[$r->socio_negocio_id] ?? '—',
            'total'   => (float) $r->total,
        ])->toArray();
    }

    /**
     * 2) TOP PRODUCTOS por cantidad (y muestra ingreso).
     */
    protected function calcularTopProductos(): void
    {
        $cantidad = PedidoDetalle::query()
            ->join('pedidos', 'pedidos.id', '=', 'pedido_detalles.pedido_id')
            ->where('pedidos.estado', '!=', 'cancelado')
            ->whereBetween('pedidos.fecha', [$this->desde, $this->hasta])
            ->selectRaw("
                pedido_detalles.producto_id,
                SUM(pedido_detalles.cantidad) AS cant,
                SUM(pedido_detalles.cantidad * pedido_detalles.precio_unitario) AS ingreso
            ")
            ->groupBy('pedido_detalles.producto_id')
            ->orderByDesc('cant')
            ->limit(10)
            ->get();

        $ids = $cantidad->pluck('producto_id');
        $nombres = Producto::whereIn('id', $ids)->pluck('nombre', 'id');

        $this->topProductos = $cantidad->map(fn($r) => [
            'producto' => $nombres[$r->producto_id] ?? '—',
            'cantidad' => (float) $r->cant,
            'ingreso'  => (float) $r->ingreso,
        ])->toArray();
    }

    /**
     * 3) ¿Qué compró cada cliente? (para los TOP 20 por ventas del periodo)
     */
    protected function calcularComprasPorCliente(): void
    {
        $topClientesIds = PedidoDetalle::query()
            ->join('pedidos', 'pedidos.id', '=', 'pedido_detalles.pedido_id')
            ->where('pedidos.estado', '!=', 'cancelado')
            ->whereBetween('pedidos.fecha', [$this->desde, $this->hasta])
            ->when($this->clienteId, fn($q) => $q->where('pedidos.socio_negocio_id', $this->clienteId))
            ->selectRaw("
                pedidos.socio_negocio_id,
                SUM(pedido_detalles.cantidad * pedido_detalles.precio_unitario) AS total
            ")
            ->groupBy('pedidos.socio_negocio_id')
            ->orderByDesc('total')
            ->limit(20)
            ->pluck('socio_negocio_id');

        $rows = PedidoDetalle::query()
            ->join('pedidos','pedidos.id','=','pedido_detalles.pedido_id')
            ->where('pedidos.estado','!=','cancelado')
            ->whereBetween('pedidos.fecha', [$this->desde, $this->hasta])
            ->when($this->clienteId, fn($q) => $q->where('pedidos.socio_negocio_id', $this->clienteId))
            ->whereIn('pedidos.socio_negocio_id', $topClientesIds)
            ->selectRaw("
                pedidos.socio_negocio_id,
                pedido_detalles.producto_id,
                SUM(pedido_detalles.cantidad) AS cantidad,
                SUM(pedido_detalles.cantidad * pedido_detalles.precio_unitario) AS ingreso
            ")
            ->groupBy('pedidos.socio_negocio_id','pedido_detalles.producto_id')
            ->get();

        $clientes  = Socio::whereIn('id', $rows->pluck('socio_negocio_id'))->pluck('razon_social','id');
        $productos = Producto::whereIn('id', $rows->pluck('producto_id'))->pluck('nombre','id');

        $agrupado = [];
        foreach ($rows as $r) {
            $cId = $r->socio_negocio_id;
            $agrupado[$cId] ??= ['cliente'=>$clientes[$cId] ?? '—','total'=>0.0,'items'=>[]];
            $agrupado[$cId]['items'][] = [
                'producto' => $productos[$r->producto_id] ?? '—',
                'cantidad' => (float) $r->cantidad,
                'ingreso'  => (float) $r->ingreso,
            ];
            $agrupado[$cId]['total'] += (float) $r->ingreso;
        }

        uasort($agrupado, fn($a,$b) => $b['total'] <=> $a['total']);
        $this->comprasPorCliente = array_values($agrupado);
    }

    /**
     * 4) Entradas de mercancía realizadas al socio (filtrables por fecha/cliente)
     */
    protected function calcularEntradasPorSocio(): void
    {
        $entradas = EntradaMercancia::with([
                'socioNegocio:id,razon_social',
                'detalles.producto:id,nombre',
                'detalles.bodega:id,nombre',
            ])
            ->whereBetween('fecha_contabilizacion', [$this->desde, $this->hasta])
            ->when($this->clienteId, fn($q) => $q->where('socio_negocio_id', $this->clienteId))
            ->orderByDesc('fecha_contabilizacion')
            ->limit(200)
            ->get();

        $this->entradasSocio = $entradas->map(function ($e) {
            // si no casteaste la fecha en el modelo, parsea por si viene string
            $fecha = is_string($e->fecha_contabilizacion)
                ? Carbon::parse($e->fecha_contabilizacion)->format('Y-m-d')
                : optional($e->fecha_contabilizacion)->format('Y-m-d');

            $total = (float) $e->detalles->sum(
                fn($d) => (float)$d->cantidad * (float)($d->precio_unitario ?? 0)
            );

            return [
                'id'            => $e->id,
                'fecha'         => $fecha ?? '—',
                'cliente'       => optional($e->socioNegocio)->razon_social ?? '—',
                'lista_precio'  => $e->lista_precio,
                'observaciones' => $e->observaciones,
                'total'         => $total,
                'items'         => $e->detalles->map(fn($d) => [
                    'producto'        => $d->producto->nombre ?? '—',
                    'bodega'          => optional($d->bodega)->nombre ?? '—',
                    'cantidad'        => (float) $d->cantidad,
                    'precio_unitario' => (float) ($d->precio_unitario ?? 0),
                    'subtotal'        => (float) $d->cantidad * (float) ($d->precio_unitario ?? 0),
                ])->toArray(),
            ];
        })->toArray();

        $this->entradasSocioTotal = (float) array_sum(array_column($this->entradasSocio, 'total'));
    }

    public function render()
    {
        return view('livewire.finanzas.reportes', [
            'clientesOptions'     => $this->clientesOptions,
            'topClientes'         => $this->topClientes,
            'topProductos'        => $this->topProductos,
            'comprasPorCliente'   => $this->comprasPorCliente,
            'entradasSocio'       => $this->entradasSocio,
            'entradasSocioTotal'  => $this->entradasSocioTotal,
            'cxpResumen'          => $this->cxpResumen,
            'cxpFacturas'         => $this->cxpFacturas,
        ]);
    }
}
