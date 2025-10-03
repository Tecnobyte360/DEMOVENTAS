<?php

namespace App\Livewire\Finanzas;

use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\PendingToast;

use App\Models\Pedidos\Pedido;
use App\Models\Inventario\EntradaDetalle;
use App\Models\InventarioRuta\GastoRuta;
use App\Models\Devoluciones\DevolucionDetalle;
use App\Models\Pago;

class Liquidacion extends Component
{
    // Filtros
    public string $fechaInicio;
    public string $fechaFin;

    // Estado / datos
    public array $resumenConductores = [];
    public array $gastosAdministrativos = [];
    public array $todosLosGastos = [];

    // Subfilas abiertas
    public ?int $filaCreditosAbierta = null;
    public ?int $filaTransferenciasAbierta = null;

    // Pedido seleccionado para detalle inline
    public array $pedidoSeleccionado = [];

    public function mount(): void
    {
        $this->fechaInicio = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->fechaFin    = Carbon::now()->format('Y-m-d');
        $this->generarResumenConductores();
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['fechaInicio', 'fechaFin'], true)) {
            $this->generarResumenConductores();
        }
    }

    public function buscar(): void
    {
        $this->generarResumenConductores();
    }

    public function abrirCreditos(int $idx): void
    {
        $this->filaCreditosAbierta = ($this->filaCreditosAbierta === $idx) ? null : $idx;
        if (!is_null($this->filaCreditosAbierta)) {
            $this->filaTransferenciasAbierta = null;
        }
        if (is_null($this->filaCreditosAbierta)) {
            $this->pedidoSeleccionado = [];
        }
    }

    public function abrirTransferencias(int $idx): void
    {
        $this->filaTransferenciasAbierta = ($this->filaTransferenciasAbierta === $idx) ? null : $idx;
        if (!is_null($this->filaTransferenciasAbierta)) {
            $this->filaCreditosAbierta = null;
        }
        if (is_null($this->filaTransferenciasAbierta)) {
            $this->pedidoSeleccionado = [];
        }
    }

    /**
     * Ver pedido y abrir la subfila de la sección correspondiente.
     * $seccion: 'creditos' | 'transferencias'
     */
    public function verPedido(int $pedidoId, ?int $idx = null, string $seccion = 'creditos'): void
    {
        // Toggle si clickean el mismo
        if (($this->pedidoSeleccionado['id'] ?? null) === $pedidoId) {
            $this->pedidoSeleccionado = [];
            return;
        }

        $pedido = Pedido::with([
            'usuario:id,name',
            'socioNegocio:id,razon_social',
            'ruta',
            'detalles.producto:id,nombre',
            'detalles.precioLista:id,nombre',
            'pagos',
        ])->find($pedidoId);

        if (!$pedido) {
            PendingToast::create()->error()->message('Pedido no encontrado.')->duration(5000);
            return;
        }

        // Abrir subfila adecuada
        if ($idx !== null) {
            if ($seccion === 'transferencias') {
                $this->filaTransferenciasAbierta = $idx;
                $this->filaCreditosAbierta = null;
            } else {
                $this->filaCreditosAbierta = $idx;
                $this->filaTransferenciasAbierta = null;
            }
        }

        $nombreRuta = optional($pedido->ruta)->nombre
            ?? ($pedido->ruta->nombre_ruta ?? null)
            ?? ($pedido->ruta->descripcion ?? null)
            ?? '—';

        $this->pedidoSeleccionado = [
            'id'        => $pedido->id,
            'fecha'     => optional($pedido->fecha)->format('d/m/Y') ?? '—',
            'conductor' => optional($pedido->usuario)->name ?? '—',
            'cliente'   => optional($pedido->socioNegocio)->razon_social ?? '—',
            'ruta'      => $nombreRuta,
            'tipo_pago' => $pedido->tipo_pago,
            'valor'     => (float) ($pedido->valor_credito ?? $pedido->valor_pagado ?? 0),

            'detalles'  => $pedido->detalles->map(function ($d) {
                $precioBase  = (float) (EntradaDetalle::where('producto_id', $d->producto_id)
                                ->orderByDesc('created_at')->value('precio_unitario') ?? 0);
                $precioVenta = (float) $d->precio_unitario;
                return [
                    'producto'     => $d->producto->nombre ?? '—',
                    'lista'        => $d->precioLista->nombre ?? '—',
                    'cantidad'     => (float) $d->cantidad,
                    'precio_base'  => $precioBase,
                    'precio_venta' => $precioVenta,
                    'subtotal'     => $precioVenta * (float) $d->cantidad,
                    'costo'        => $precioBase * (float) $d->cantidad,
                ];
            })->toArray(),

            'pagos'     => $pedido->pagos->map(fn($p) => [
                'fecha'  => optional($p->created_at)->format('d/m/Y H:i') ?? '—',
                'monto'  => (float) $p->monto,
                'metodo' => $p->metodo_pago,
                'obs'    => $p->observaciones ?? '',
            ])->toArray(),
        ];
    }

    public function generarResumenConductores(): void
    {
        try {
            $resumen = [];
            $yaSumadoGastos = [];
            $yaProcesadoDevolucion = [];

            $pedidos = Pedido::with([
                    'detalles.producto',
                    'detalles.precioLista',
                    'ruta.conductores',
                    'pagos',
                    'socioNegocio:id,razon_social',
                    'usuario:id,name',
                ])
                ->where('estado', '!=', 'cancelado')
                ->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin])
                ->get();

            foreach ($pedidos as $pedido) {
                $fecha = $pedido->fecha->format('Y-m-d');

                foreach ($pedido->ruta->conductores ?? [] as $conductor) {
                    $clave = "{$conductor->id}_{$fecha}";

                    if (!isset($resumen[$clave])) {
                        $resumen[$clave] = [
                            'nombre'                    => $conductor->name,
                            'fecha'                     => $fecha,
                            'detalles'                  => [],
                            'total_facturado'           => 0.0,
                            'total_gastos'              => 0.0,
                            'total_devoluciones'        => 0.0,
                            'total_pagos_contado'       => 0.0,
                            'total_pagos_credito'       => 0.0,
                            'total_pagos_transferencia' => 0.0,
                            'pagos_credito_anteriores'  => 0.0,
                            'pagos_credito_anteriores_detalle' => [],
                            'total_liquidar'            => 0.0,
                            'gastos_detalle'            => [],
                            'utilidad'                  => 0.0,
                            'creditos_detalle'          => [],
                            'transferencias_detalle'    => [],   // 👈 NUEVO
                        ];
                    }

                    // Detalles de venta
                    foreach ($pedido->detalles as $d) {
                        $costoUnitario = (float) (EntradaDetalle::where('producto_id', $d->producto_id)
                            ->orderByDesc('created_at')->value('precio_unitario') ?? 0);

                        $precioVenta = (float) $d->precio_unitario;
                        $cantidad    = (float) $d->cantidad;
                        $subtotal    = $precioVenta * $cantidad;
                        $utilidad    = ($precioVenta - $costoUnitario) * $cantidad;

                        $resumen[$clave]['detalles'][] = [
                            'tipo'         => 'venta',
                            'producto'     => $d->producto->nombre ?? '—',
                            'cantidad'     => $cantidad,
                            'precio_base'  => $costoUnitario,
                            'precio_venta' => $precioVenta,
                            'lista'        => $d->precioLista->nombre ?? 'Precio Base',
                            'subtotal'     => $subtotal,
                            'utilidad'     => $utilidad,
                            'costo'        => $costoUnitario * $cantidad,
                        ];

                        $resumen[$clave]['total_facturado'] += $subtotal;
                        $resumen[$clave]['utilidad']       += $utilidad;
                    }

                    // Pedido sin detalles
                    if ($pedido->detalles->isEmpty()) {
                        if ($pedido->tipo_pago === 'credito' && $pedido->valor_credito) {
                            $resumen[$clave]['total_facturado'] += (float) $pedido->valor_credito;
                        } elseif (in_array($pedido->tipo_pago, ['contado', 'transferencia'], true) && $pedido->valor_pagado) {
                            $resumen[$clave]['total_facturado'] += (float) $pedido->valor_pagado;
                        }
                    }

                    // Gastos por ruta y fecha
                    if (!isset($yaSumadoGastos[$clave])) {
                        $gRuta = GastoRuta::with('tipoGasto')
                            ->where('ruta_id', $pedido->ruta_id)
                            ->whereDate('created_at', $fecha)
                            ->get();

                        $resumen[$clave]['total_gastos'] = (float) $gRuta->sum('monto');

                        $porTipo = $gRuta->groupBy(fn($x) => optional($x->tipoGasto)->nombre ?? 'Sin clasificar')
                                         ->map(fn($grupo) => (float) $grupo->sum('monto'));

                        foreach ($porTipo as $tipo => $monto) {
                            $resumen[$clave]['gastos_detalle'][$tipo] = $monto;
                        }

                        $yaSumadoGastos[$clave] = true;
                    }

                    // Devoluciones
                    if (!isset($yaProcesadoDevolucion[$clave])) {
                        $devs = DevolucionDetalle::with(['producto', 'devolucion'])
                            ->whereHas('devolucion', function ($q) use ($pedido, $conductor, $fecha) {
                                $q->where('ruta_id', $pedido->ruta_id)
                                  ->where('user_id', $conductor->id)
                                  ->whereDate('fecha', $fecha);
                            })->get();

                        foreach ($devs as $dev) {
                            $costoUnitario = (float) (EntradaDetalle::where('producto_id', $dev->producto_id)
                                ->orderByDesc('created_at')->value('precio_unitario') ?? 0);

                            $precioVenta = (float) ($dev->precio_unitario ?? 0);
                            $cantidad    = (float) $dev->cantidad;
                            $subtotal    = $precioVenta * $cantidad;
                            $utilidad    = ($precioVenta - $costoUnitario) * $cantidad;

                            $resumen[$clave]['detalles'][] = [
                                'tipo'         => 'devolución',
                                'producto'     => $dev->producto->nombre ?? 'Producto',
                                'cantidad'     => -abs($cantidad),
                                'precio_base'  => $costoUnitario,
                                'precio_venta' => -abs($precioVenta),
                                'lista'        => '— Devolución',
                                'subtotal'     => -abs($subtotal),
                                'utilidad'     => -abs($utilidad),
                                'costo'        => -abs($costoUnitario * $cantidad),
                            ];

                            $resumen[$clave]['total_devoluciones'] += abs($subtotal);
                            $resumen[$clave]['utilidad']           -= abs($utilidad);
                        }

                        $yaProcesadoDevolucion[$clave] = true;
                    }

                    // Pagos
                    foreach ($pedido->pagos as $pago) {
                        switch ($pago->metodo_pago) {
                            case 'credito':
                                $resumen[$clave]['total_pagos_credito'] += (float) $pago->monto;

                                $resumen[$clave]['creditos_detalle'][] = [
                                    'pedido_id'   => $pedido->id,
                                    'cliente'     => optional($pedido->socioNegocio)->razon_social ?? '—',
                                    'conductor'   => optional($pedido->usuario)->name ?? '—',
                                    'fecha'       => optional($pago->created_at)->format('Y-m-d H:i') ?? $pedido->fecha->format('Y-m-d'),
                                    'monto'       => (float) $pago->monto,
                                    'observacion' => $pago->observaciones ?? '',
                                    'origen'      => 'Pago crédito',
                                ];
                                break;

                            case 'transferencia':
                                $resumen[$clave]['total_pagos_transferencia'] += (float) $pago->monto;

                                $resumen[$clave]['transferencias_detalle'][] = [   // 👈 NUEVO
                                    'pedido_id'   => $pedido->id,
                                    'cliente'     => optional($pedido->socioNegocio)->razon_social ?? '—',
                                    'conductor'   => optional($pedido->usuario)->name ?? '—',
                                    'fecha'       => optional($pago->created_at)->format('Y-m-d H:i') ?? $pedido->fecha->format('Y-m-d'),
                                    'monto'       => (float) $pago->monto,
                                    'observacion' => $pago->observaciones ?? '',
                                    'origen'      => 'Pago transferencia',
                                ];
                                break;

                            default: // contado
                                $resumen[$clave]['total_pagos_contado'] += (float) $pago->monto;
                                break;
                        }
                    }

                    // Pedido sin pagos
                    if ($pedido->pagos->isEmpty()) {
                        if ($pedido->tipo_pago === 'credito' && $pedido->valor_credito) {
                            $resumen[$clave]['total_pagos_credito'] += (float) $pedido->valor_credito;
                            $resumen[$clave]['creditos_detalle'][] = [
                                'pedido_id'   => $pedido->id,
                                'cliente'     => optional($pedido->socioNegocio)->razon_social ?? '—',
                                'conductor'   => optional($pedido->usuario)->name ?? '—',
                                'fecha'       => $pedido->fecha->format('Y-m-d'),
                                'monto'       => (float) $pedido->valor_credito,
                                'observacion' => 'Venta a crédito (sin pagos registrados)',
                                'origen'      => 'Venta crédito',
                            ];
                        } elseif ($pedido->tipo_pago === 'transferencia' && $pedido->valor_pagado) {
                            $resumen[$clave]['total_pagos_transferencia'] += (float) $pedido->valor_pagado;
                            $resumen[$clave]['transferencias_detalle'][] = [   // 👈 NUEVO
                                'pedido_id'   => $pedido->id,
                                'cliente'     => optional($pedido->socioNegocio)->razon_social ?? '—',
                                'conductor'   => optional($pedido->usuario)->name ?? '—',
                                'fecha'       => $pedido->fecha->format('Y-m-d'),
                                'monto'       => (float) $pedido->valor_pagado,
                                'observacion' => 'Venta por transferencia (sin pagos registrados)',
                                'origen'      => 'Venta transferencia',
                            ];
                        } elseif ($pedido->tipo_pago === 'contado' && $pedido->valor_pagado) {
                            $resumen[$clave]['total_pagos_contado'] += (float) $pedido->valor_pagado;
                        }
                    }
                }
            }

            // Pagos anteriores cobrados hoy en contado (tu lógica original)
            $pagosExtra = Pago::with(['pedido.ruta.conductores', 'usuario'])
                ->whereBetween('created_at', [$this->fechaInicio . ' 00:00:00', $this->fechaFin . ' 23:59:59'])
                ->where('metodo_pago', 'contado')
                ->get()
                ->filter(function ($pago) {
                    return $pago->pedido
                        && Carbon::parse($pago->pedido->fecha)->lt(Carbon::parse($pago->created_at)->startOfDay());
                });

            foreach ($pagosExtra as $pago) {
                $fechaPago   = Carbon::parse($pago->created_at)->format('Y-m-d');
                $usuarioPago = $pago->usuario;

                if ($usuarioPago) {
                    $clave = "{$usuarioPago->id}_{$fechaPago}";

                    if (!isset($resumen[$clave])) {
                        $resumen[$clave] = [
                            'nombre'                    => $usuarioPago->name,
                            'fecha'                     => $fechaPago,
                            'detalles'                  => [],
                            'total_facturado'           => 0,
                            'total_gastos'              => 0,
                            'total_devoluciones'        => 0,
                            'total_pagos_contado'       => 0,
                            'total_pagos_credito'       => 0,
                            'total_pagos_transferencia' => 0,
                            'pagos_credito_anteriores'  => 0,
                            'pagos_credito_anteriores_detalle' => [],
                            'total_liquidar'            => 0,
                            'gastos_detalle'            => [],
                            'utilidad'                  => 0,
                            'creditos_detalle'          => [],
                            'transferencias_detalle'    => [],
                        ];
                    }

                    $resumen[$clave]['pagos_credito_anteriores'] += (float) $pago->monto;

                    $resumen[$clave]['pagos_credito_anteriores_detalle'][] = [
                        'pedido_id'          => $pago->pedido_id,
                        'fecha_pedido'       => optional($pago->pedido)->fecha?->format('d/m/Y') ?? 'Sin fecha',
                        'conductor_original' => optional($pago->pedido->ruta->conductores->first())->name ?? '—',
                    ];
                }
            }

            // Total a liquidar
            foreach ($resumen as $clave => &$fila) {
                $fila['total_liquidar'] =
                    ($fila['total_facturado'] ?? 0)
                    - ($fila['total_devoluciones'] ?? 0)
                    - ($fila['total_gastos'] ?? 0)
                    - ($fila['total_pagos_transferencia'] ?? 0);
            }

            // Gastos admin
            $this->todosLosGastos = GastoRuta::with('tipoGasto')
                ->whereBetween('created_at', [$this->fechaInicio . ' 00:00:00', $this->fechaFin . ' 23:59:59'])
                ->get()
                ->toArray();

            $this->gastosAdministrativos = collect($this->todosLosGastos)->whereNull('ruta_id')->values()->toArray();

            // Persistimos
            $this->resumenConductores = array_values($resumen);

            PendingToast::create()
                ->{$this->resumenConductores ? 'success' : 'info'}()
                ->message($this->resumenConductores ? 'Liquidación generada correctamente.' : 'ℹ No hay registros.')
                ->duration(4000);

        } catch (\Throwable $e) {
            Log::error('Error liquidación', ['e' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            PendingToast::create()->error()->message('Error al calcular la liquidación.')->duration(8000);
        }
    }

    public function render()
    {
        $r   = collect($this->resumenConductores);
        $tf  = (float) $r->sum('total_facturado');
        $tg  = (float) $r->sum('total_gastos');
        $td  = (float) $r->sum('total_devoluciones');
        $tc  = (float) $r->sum('total_pagos_contado');
        $tcr = (float) $r->sum('total_pagos_credito');
        $tt  = (float) $r->sum('total_pagos_transferencia');
        $ta  = (float) $r->sum('pagos_credito_anteriores');
        $na  = $tf - $tg - $td - $tt - $ta;

        return view('livewire.finanzas.liquidacion', [
            'resumenConductores'         => collect($this->resumenConductores),
            'totalFacturado'             => $tf,
            'totalGastos'                => $tg,
            'totalDevoluciones'          => $td,
            'totalPagosContado'          => $tc,
            'totalPagosCredito'          => $tcr,
            'totalPagosTransferencia'    => $tt,
            'totalPagosAnteriores'       => $ta,
            'netoAcumulado'              => $na,
            'totalGastosAdministrativos' => (float) collect($this->gastosAdministrativos)->sum('monto'),
            'filaCreditosAbierta'        => $this->filaCreditosAbierta,
            'filaTransferenciasAbierta'  => $this->filaTransferenciasAbierta,
            'pedidoSeleccionado'         => $this->pedidoSeleccionado,
        ]);
    }
}
