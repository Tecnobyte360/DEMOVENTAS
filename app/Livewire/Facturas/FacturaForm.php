<?php

namespace App\Livewire\Facturas;


use App\Models\Bodega;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

use App\Models\Serie\Serie;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Productos\Producto;

use App\Models\CondicionPago\CondicionPago;
use App\Models\ConfiguracionEmpresas\Empresa;
use App\Models\cotizaciones\cotizacione as CotizacionModel;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Factura\Factura;
use App\Models\Productos\ProductoCuentaTipo;
use App\Services\ContabilidadService;
use Masmerise\Toaster\PendingToast;
use App\Models\Impuestos\Impuesto;
use App\Services\InventarioService;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class FacturaForm extends Component
{
    public ?Factura $factura = null;
    public string $documento = 'factura';
    public string $modo = 'venta';
    public ?Serie $serieDefault = null;
    public ?int $bodega_predeterminada_empresa_id = null;
    public ?int $serie_id = null;
    public ?int $socio_negocio_id = null;
    public string $fecha = '';
    public ?string $vencimiento = null;
    public string $tipo_pago = 'contado';
    public ?int $plazo_dias = null;
    public ?string $terminos_pago = null;
    public ?string $notas = null;
    public string $moneda = 'COP';
    public bool $habilitarActualizar = false;
    private ?string $originalHash = null;
    public string $estado = 'borrador';
    public array $lineas = [];
    public array $stockVista = [];
    public bool $showPagos = false;
    public ?int $cuenta_cobro_id = null;
    public ?int $cotizacion_id = null;
    /** Condición de pago seleccionada (contado / crédito del cliente) */
    public ?int $condicion_pago_id = null;
    public array $productosSeleccionados = [];
    /** NUEVO: controlas si quieres auto-emitir al pagar 100% contado */
    public bool $autoEmitirContado = false;

    protected $rules = [
        'serie_id'                     => 'required|integer|exists:series,id',
        'socio_negocio_id'             => 'required|integer|exists:socio_negocios,id',
        'fecha'                        => 'required|date',
        'vencimiento'                  => 'required|date|after_or_equal:fecha',
        'tipo_pago'                    => 'required|in:contado,credito',
        'plazo_dias'                   => 'nullable|integer|min:1|max:365|required_if:tipo_pago,credito',
        'terminos_pago'                => 'required|string|max:255',
        'moneda'                       => 'required|string|size:3',
        'cuenta_cobro_id'              => 'required|integer|exists:plan_cuentas,id',
        'condicion_pago_id'            => 'nullable|integer|exists:condicion_pagos,id',
        'cotizacion_id' => 'nullable|integer|exists:cotizaciones,id',
        'lineas'                       => 'required|array|min:1',
        'lineas.*.producto_id'         => 'required|integer|exists:productos,id',
        'lineas.*.cuenta_ingreso_id'   => 'required|integer|exists:plan_cuentas,id',
        'lineas.*.bodega_id'           => 'required|integer|exists:bodegas,id',
        'lineas.*.descripcion'         => 'required|string|max:255',
        'lineas.*.cantidad'            => 'required|numeric|min:0.01',
        'lineas.*.precio_unitario'     => 'required|numeric|min:0',
        'lineas.*.descuento_pct'       => 'required|numeric|min:0|max:100',
        'lineas.*.impuesto_id'         => 'nullable|integer|exists:impuestos,id',
        'lineas.*.impuesto_pct'        => 'required|numeric|min:0|max:100',
    ];

    protected array $validationAttributes = [
        'serie_id' => 'serie',
        'socio_negocio_id' => 'cliente',
        'vencimiento' => 'vencimiento',
        'plazo_dias' => 'plazo en días',
        'terminos_pago' => 'términos',
        'cuenta_cobro_id' => 'cuenta para cobrar del cliente',
        'condicion_pago_id' => 'condición de pago',
        'cotizacion_id' => 'cotización',
        'lineas' => 'líneas',
        'lineas.*.producto_id' => 'producto',
        'lineas.*.cuenta_ingreso_id' => 'cuenta de ingreso',
        'lineas.*.bodega_id' => 'bodega',
        'lineas.*.descripcion' => 'descripción',
        'lineas.*.cantidad' => 'cantidad',
        'lineas.*.precio_unitario' => 'precio unitario',
        'lineas.*.descuento_pct' => 'descuento (%)',
        'lineas.*.impuesto_id' => 'indicador de impuesto',
        'lineas.*.impuesto_pct' => 'porcentaje de impuesto',
    ];

    #[On('abrir-factura')]
    public function abrir(int $id): void
    {
        $this->cargarFactura($id);

        // ✅ fuerza sincronización de TomSelect con lineas actuales
        $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
    }

    public function mount(?int $id = null): void
    {
        try {
            $this->fecha = now()->toDateString();

            // 👇 Detectar serie según modo
            $this->documento = $this->modo === 'compra' ? 'facturacompra' : 'factura';
            $this->serieDefault = Serie::defaultParaCodigo($this->documento);

            // ✅ Auto-emitir SOLO en modo venta (para compra NO)
            $this->autoEmitirContado = ($this->modo === 'venta');

            // ✅ Empresa activa / primera empresa
            $empresa = Empresa::query()
                ->where('is_activa', true)
                ->first() ?? Empresa::query()->first();

            $this->bodega_predeterminada_empresa_id = $empresa?->bodega_predeterminada_id;

            if ($id) {
                $this->cargarFactura($id);

                if (!$this->factura->serie_id && $this->serieDefault) {
                    $this->serie_id = $this->serieDefault->id;
                }

                $this->aplicarFormaPago($this->tipo_pago);

                if (empty($this->terminos_pago)) {
                    $this->terminos_pago = $this->tipo_pago === 'credito'
                        ? 'Crédito a ' . (int)($this->plazo_dias ?: 30) . ' días'
                        : 'Contado';
                }
            } else {
                $this->addLinea();
                $this->aplicarFormaPago('contado');
                $this->terminos_pago = 'Contado';

                $this->serie_id = (int)(
                    $this->factura?->serie_id
                    ?: ($this->serieDefault?->id)
                );

                if ($this->socio_negocio_id) {
                    $this->setPagoDesdeCliente((int)$this->socio_negocio_id);

                    $socio = \App\Models\SocioNegocio\SocioNegocio::with('condicionPago')
                        ->find((int)$this->socio_negocio_id);

                    $this->condicion_pago_id = $socio?->condicionPago?->id ?: null;
                }
            }

            $this->setCuentaCobroPorDefecto();
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()
                ->error()
                ->message('No se pudo inicializar el formulario de factura.')
                ->duration(7000);
        }
    }




    public function render()
    {
        try {
            $clientes = SocioNegocio::clientes()
                ->orderBy('razon_serial')
                ->orderBy('razon_social')
                ->take(200)
                ->get();
        } catch (\Throwable $e) {
            $clientes = SocioNegocio::clientes()
                ->orderBy('razon_social')
                ->take(200)
                ->get();
        }

        try {
            $this->syncProductosSeleccionados();

            $bodegas = Bodega::query()
                ->orderBy('nombre')
                ->get();

            $cuentasIngresos = PlanCuentas::query()
                ->where(function ($q) {
                    $q->where('titulo', 0)->orWhereNull('titulo');
                })
                ->where('cuenta_activa', 1)
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre']);

            $cuentasCXC = PlanCuentas::query()
                ->where('cuenta_activa', 1)
                ->where('titulo', 0)
                ->where('clase_cuenta', 'CXC_CLIENTES')
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre']);

            $cuentasCaja = PlanCuentas::query()
                ->where('cuenta_activa', 1)
                ->where('titulo', 0)
                ->whereIn('clase_cuenta', ['CAJA_GENERAL', 'BANCOS', 'CAJA'])
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre']);

            $impuestosVentas = Impuesto::activos()
                ->whereIn('aplica_sobre', ['VENTAS', 'VENTA', 'AMBOS', 'TODOS'])
                ->orderBy('prioridad')
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'porcentaje', 'monto_fijo', 'incluido_en_precio']);

            $condicionesPagoQuery = CondicionPago::query()
                ->orderBy('tipo')
                ->orderBy('nombre')
                ->select(['id', 'nombre', 'tipo', 'plazo_dias']);

            if (Schema::hasColumn('condicion_pagos', 'activo')) {
                $condicionesPagoQuery->where('activo', 1);
            }

            $condicionesPago = $condicionesPagoQuery->get();

            $cotizacionesQuery = CotizacionModel::query()
                ->with([
                    'socioNegocio:id,razon_social,nit',
                    'detalles:id,cotizacion_id,producto_id,bodega_id,cantidad,precio_unitario,descuento_pct,impuesto_pct,importe',
                ])
                ->orderByDesc('id')
                ->take(100);

            if (Schema::hasColumn('cotizaciones', 'estado')) {
                $cotizacionesQuery->whereIn('estado', ['borrador', 'enviada', 'aprobada']);
            }

            if (!empty($this->socio_negocio_id)) {
                $cotizacionesQuery->where('socio_negocio_id', (int) $this->socio_negocio_id);
            }

            $cotizaciones = $cotizacionesQuery->get([
                'id',
                'socio_negocio_id',
                'fecha',
                'vencimiento',
                'terminos_pago',
                'notas',
                'total',
                'estado',
            ]);

            return view('livewire.facturas.factura-form', [
                'clientes'               => $clientes,
                'productos'              => collect(), // catálogo ya no se carga completo
                'productosSeleccionados' => $this->productosSeleccionados,
                'bodegas'                => $bodegas,
                'series'                 => $this->serieDefault ? collect([$this->serieDefault]) : collect(),
                'serieDefault'           => $this->serieDefault,
                'cuentasIngresos'        => $cuentasIngresos,
                'cuentasCXC'             => $cuentasCXC,
                'cuentasCaja'            => $cuentasCaja,
                'impuestosVentas'        => $impuestosVentas,
                'bloqueada'              => $this->bloqueada,
                'condicionesPago'        => $condicionesPago,
                'cotizaciones'           => $cotizaciones,
            ]);
        } catch (Throwable $e) {
            report($e);

            PendingToast::create()
                ->error()
                ->message('No se pudo cargar datos auxiliares.')
                ->duration(6000);

            return view('livewire.facturas.factura-form', [
                'clientes'               => collect(),
                'productos'              => collect(),
                'productosSeleccionados' => [],
                'bodegas'                => collect(),
                'series'                 => collect(),
                'serieDefault'           => $this->serieDefault,
                'cuentasIngresos'        => collect(),
                'cuentasCXC'             => collect(),
                'cuentasCaja'            => collect(),
                'impuestosVentas'        => collect(),
                'bloqueada'              => $this->bloqueada,
                'condicionesPago'        => collect(),
                'cotizaciones'           => collect(),
            ]);
        }
    }

    /* =========================
     *  BLOQUEO / SOLO LECTURA
     * ========================= */

 public function getBloqueadaProperty(): bool
{
    $estado = $this->factura->estado ?? $this->estado ?? 'borrador';

    if (in_array($estado, ['cerrado', 'anulada', 'pagada'], true)) {
        return true;
    }

    if ($this->factura?->id) {
        $total  = round((float) ($this->factura->total ?? 0), 2);
        $pagado = round((float) ($this->factura->pagos()->sum('monto')), 2);
        $saldo  = round($total - $pagado, 2);

        if ($saldo <= 0.01 && $total > 0) {
            return true;
        }
    }

    return false;
}

 private function abortIfLocked(string $accion = 'editar'): bool
{
    if ($this->bloqueada) {
        $estado = $this->factura->estado ?? $this->estado ?? 'bloqueada';

        PendingToast::create()
            ->error()
            ->message("La factura está {$estado}; no se puede {$accion}.")
            ->duration(7000);

        return true;
    }

    return false;
}

    /* =========================
     *  HELPERS / UTILIDADES
     * ========================= */

    private function tipoIngresoId(): ?int
    {
        return cache()->remember('producto_cuenta_tipo_ingreso_id', 600, function () {
            return ProductoCuentaTipo::query()
                ->where('codigo', 'INGRESO')
                ->value('id');
        });
    }

    private function resolveCuentaIngresoParaProducto(Producto $p): ?int
    {
        // 🔑 PRIMERO: respetar la configuración mov_contable_segun
        // Si está en SUBCATEGORIA, no usar el campo directo del producto
        $modo = strtoupper((string)($p->mov_contable_segun ?? 'ARTICULO'));

        // Si está configurado para usar SUBCATEGORIA, delegar al servicio
        if ($modo === 'SUBCATEGORIA') {
            return \App\Services\ContabilidadService::cuentaSegunConfiguracion($p, 'INGRESO');
        }

        // Si está en ARTICULO, primero verificar si tiene cuenta directa
        if (!empty($p->cuenta_ingreso_id)) {
            return (int) $p->cuenta_ingreso_id;
        }

        // Fallback: delegar al servicio (buscará en relaciones o subcategoría)
        return \App\Services\ContabilidadService::cuentaSegunConfiguracion($p, 'INGRESO');
    }

   private function normalizeLinea(array &$l): void
{
    $rawCant = $l['cantidad'] ?? null;

    if ($rawCant === '' || $rawCant === null) {
        $l['cantidad'] = null;
    } else {
        $cant = (float) $rawCant;
        $l['cantidad'] = round(is_finite($cant) ? $cant : 0, 3);

        if ($l['cantidad'] < 0) {
            $l['cantidad'] = 0;
        }
    }

    $precio = (float)($l['precio_unitario'] ?? 0);
    $desc   = (float)($l['descuento_pct'] ?? 0);
    $iva    = (float)($l['impuesto_pct'] ?? 0);

    $l['precio_unitario'] = max(0.0, round(is_finite($precio) ? $precio : 0, 2));
    $l['descuento_pct']   = min(100.0, max(0.0, round(is_finite($desc) ? $desc : 0, 3)));
    $l['impuesto_pct']    = min(100.0, max(0.0, round(is_finite($iva) ? $iva : 0, 3)));
}

    public function updated($name, $value): void
    {
        if ($this->bloqueada) return;

        if (preg_match('/^lineas\.(\d+)\.producto_id$/', $name, $m)) {
            $i = (int) $m[1];
            $this->setProducto($i, $value);
            $this->refreshStockLinea($i);
            $this->resetErrorBag();
            $this->resetValidation();
            $this->dispatch('$refresh');
            return;
        }

        if (preg_match('/^lineas\.(\d+)\.bodega_id$/', $name, $m)) {
            $i = (int) $m[1];
            $this->refreshStockLinea($i);
            return;
        }

        if (preg_match('/^lineas\.(\d+)\.(cantidad|precio_unitario|descuento_pct|impuesto_pct)$/', $name, $m)) {
            $i = (int) $m[1];
            if (isset($this->lineas[$i])) {
                $this->normalizeLinea($this->lineas[$i]);
                $this->dispatch('$refresh');
            }
            return;
        }

        if ($name === 'fecha') $this->aplicarFormaPago($this->tipo_pago);
        if ($name === 'plazo_dias' && $this->tipo_pago === 'credito') {
            $d = max((int)$this->plazo_dias, 1);
            $this->plazo_dias  = $d;
            $this->vencimiento = \Carbon\Carbon::parse($this->fecha)->addDays($d)->toDateString();
        }
    }

    /** 🔄 Cliente */
    public function updatedSocioNegocioId($val): void
    {
        if ($this->bloqueada) return;

        $id = (int) $val;
        $this->setCuentaDesdeCliente($id);
        $this->setPagoDesdeCliente($id);

        $socio = SocioNegocio::with('condicionPago')->find($id);
        $this->condicion_pago_id = $socio?->condicionPago?->id ?: null;

        $this->dispatch('$refresh');
    }

    /** 🔄 Select condición pago */
    public function updatedCondicionPagoId($val): void
    {
        if ($this->bloqueada) return;
        $cond = $val ? CondicionPago::find((int)$val) : null;
        if (!$cond) return;

        $this->tipo_pago   = $cond->tipo === 'credito' ? 'credito' : 'contado';
        $this->plazo_dias  = $this->tipo_pago === 'credito' ? (int)($cond->plazo_dias ?: 30) : null;
        $this->terminos_pago = $this->tipo_pago === 'credito'
            ? 'Crédito a ' . (int)$this->plazo_dias . ' días'
            : 'Contado';

        // 🔽 clave: recalcula vencimiento y flag
        $this->aplicarFormaPago($this->tipo_pago);

        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('$refresh');
    }


    public function updatedTipoPago($val): void
    {
        if ($this->bloqueada) return;

        $this->aplicarFormaPago($val);
        $this->terminos_pago = $val === 'contado'
            ? 'Contado'
            : 'Crédito a ' . (int)($this->plazo_dias ?: 30) . ' días';
    }
    #[On('set-producto-linea')]
    public function onSetProductoLinea($index, $productoId = null): void
    {
        if ($this->bloqueada) return;

        $i = (int) $index;
        $pid = $productoId ? (int) $productoId : null;

        // ✅ usa tu lógica existente (asigna cuenta_ingreso_id, precio, impuesto, etc.)
        $this->setProducto($i, $pid);

        // ✅ recalcula stock al cambiar producto
        $this->refreshStockLinea($i);

        // limpieza visual
        $this->resetErrorBag();
        $this->resetValidation();

        // refresca vista
        $this->dispatch('$refresh');
    }
    public function setCotizacion($cotizacionId = null): void
    {
        $this->cotizacion_id = $cotizacionId ? (int) $cotizacionId : null;
        $this->dispatch('sync-cotizacion-tomselect', cotizacionId: $this->cotizacion_id);
    }
    public function updatedCotizacionId($value): void
    {
        $this->dispatch('sync-cotizacion-tomselect', cotizacionId: $this->cotizacion_id);
    }


  private function cargarFactura(int $id): void
{
    try {
        $f = Factura::with(['detalles', 'pagos'])->findOrFail($id);
        $f->recalcularTotales()->save();
        $f = $f->fresh(['detalles', 'pagos']);

        // ✅ Si ya quedó totalmente pagada, marcar estado pagada
        $total  = round((float) ($f->total ?? 0), 2);
        $pagado = round((float) $f->pagos()->sum('monto'), 2);
        $saldo  = round($total - $pagado, 2);

        if ($saldo <= 0.01 && $total > 0 && !in_array($f->estado, ['pagada', 'anulada'], true)) {
            $data = ['estado' => 'pagada'];

            if (Schema::hasColumn('facturas', 'pagado')) {
                $data['pagado'] = $pagado;
            }

            if (Schema::hasColumn('facturas', 'saldo')) {
                $data['saldo'] = 0;
            }

            $f->update($data);
            $f = $f->fresh(['detalles', 'pagos']);
        }

        $this->factura = $f;

        // =========================
        // Cabecera
        // =========================
        $this->fill($f->only([
            'cotizacion_id',
            'serie_id',
            'socio_negocio_id',
            'fecha',
            'vencimiento',
            'tipo_pago',
            'plazo_dias',
            'terminos_pago',
            'notas',
            'moneda',
            'estado',
            'cuenta_cobro_id',
            'condicion_pago_id',
        ]));

        // =========================
        // Líneas (desde DB)
        // =========================
        $this->lineas = $f->detalles->map(function ($d) {
            $cuentaId = $d->cuenta_ingreso_id ? (int) $d->cuenta_ingreso_id : null;

            if (!$cuentaId && $d->producto_id) {
                $p = Producto::with(['cuentas:id,producto_id,plan_cuentas_id,tipo_id'])
                    ->find($d->producto_id);

                if ($p) {
                    $cuentaId = $this->resolveCuentaIngresoParaProducto($p);
                }
            }

            $l = [
                'id'                => $d->id,
                'producto_id'       => $d->producto_id ? (int) $d->producto_id : null,
                'cuenta_ingreso_id' => $cuentaId,
                'bodega_id'         => $d->bodega_id ? (int) $d->bodega_id : null,
                'descripcion'       => $d->descripcion,
                'cantidad'          => is_null($d->cantidad) ? null : (float) $d->cantidad,
                'precio_unitario'   => (float) $d->precio_unitario,
                'descuento_pct'     => (float) $d->descuento_pct,
                'impuesto_id'       => $d->impuesto_id ? (int) $d->impuesto_id : null,
                'impuesto_pct'      => (float) $d->impuesto_pct,
            ];

            $this->normalizeLinea($l);
            return $l;
        })->toArray();

        // ✅ IMPORTANTE:
        // NO llamar setProducto() aquí, porque eso vuelve a poner
        // el precio original del producto y pisa el precio guardado.
        foreach ($this->lineas as $i => $l) {
            $this->refreshStockLinea($i);
        }

        $this->syncProductosSeleccionados();

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
        $this->dispatch('$refresh');
    } catch (Throwable $e) {
        report($e);

        PendingToast::create()
            ->error()
            ->message('No se pudo cargar la factura.')
            ->duration(7000);
    }
}


   public function addLinea(): void
{
    if ($this->bloqueada) return;

    $l = [
        'producto_id'       => null,
        'cuenta_ingreso_id' => null,
        'bodega_id'         => $this->bodega_predeterminada_empresa_id,
        'descripcion'       => null,
        'cantidad'          => null,
        'precio_unitario'   => 0,
        'descuento_pct'     => 0,
        'impuesto_id'       => null,
        'impuesto_pct'      => 0,
    ];

    $this->normalizeLinea($l);
    $this->lineas[] = $l;

    $ultimoIndex = array_key_last($this->lineas);
    $this->refreshStockLinea($ultimoIndex);

    $this->syncProductosSeleccionados();
    $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
    $this->dispatch('$refresh');
}
    private function aplicarBodegaPredeterminadaALineasVacias(): void
    {
        if (!$this->bodega_predeterminada_empresa_id) {
            return;
        }

        foreach ($this->lineas as &$linea) {
            if (empty($linea['bodega_id'])) {
                $linea['bodega_id'] = $this->bodega_predeterminada_empresa_id;
            }
        }
    }
    public function removeLinea(int $i): void
    {
        if ($this->bloqueada) return;
        if (!isset($this->lineas[$i])) return;

        array_splice($this->lineas, $i, 1);

        $this->syncProductosSeleccionados();
        $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
        $this->dispatch('$refresh');
    }


 public function setProducto(int $i, $id): void
{
    if ($this->bloqueada) {
        return;
    }

    try {
        if (!isset($this->lineas[$i])) {
            return;
        }

        $prodId = $id ? (int) $id : null;
        $this->lineas[$i]['producto_id'] = $prodId;

        if (!$prodId) {
            $this->lineas[$i]['cuenta_ingreso_id'] = null;
            $this->lineas[$i]['precio_unitario']   = 0.0;
            $this->lineas[$i]['impuesto_id']       = null;
            $this->lineas[$i]['impuesto_pct']      = 0.0;
            $this->lineas[$i]['descripcion']       = null;

            // ✅ refrescar stock aunque limpie producto
            $this->refreshStockLinea($i);

            $this->normalizeLinea($this->lineas[$i]);
            $this->syncProductosSeleccionados();
            $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
            $this->dispatch('$refresh');
            return;
        }

        $selects = ['id', 'nombre'];

        if (Schema::hasColumn('productos', 'precio')) {
            $selects[] = 'precio';
        }

        if (Schema::hasColumn('productos', 'precio_venta')) {
            $selects[] = 'precio_venta';
        }

        if (Schema::hasColumn('productos', 'mov_contable_segun')) {
            $selects[] = 'mov_contable_segun';
        }

        if (Schema::hasColumn('productos', 'cuenta_ingreso_id')) {
            $selects[] = 'cuenta_ingreso_id';
        }

        if (Schema::hasColumn('productos', 'codigo')) {
            $selects[] = 'codigo';
        }

        if (Schema::hasColumn('productos', 'ItemCode')) {
            $selects[] = 'ItemCode';
        }

        if (Schema::hasColumn('productos', 'es_inventariable')) {
            $selects[] = 'es_inventariable';
        }

        $p = Producto::with([
            'impuesto:id,nombre,codigo,porcentaje,monto_fijo,incluido_en_precio,aplica_sobre,activo,vigente_desde,vigente_hasta',
            'cuentaIngreso:id,codigo,nombre',
            'cuentas:id,producto_id,plan_cuentas_id,tipo_id',
        ])
            ->select($selects)
            ->find($prodId);

        if (!$p) {
            $this->lineas[$i]['cuenta_ingreso_id'] = null;
            $this->lineas[$i]['precio_unitario']   = 0.0;
            $this->lineas[$i]['impuesto_id']       = null;
            $this->lineas[$i]['impuesto_pct']      = 0.0;
            $this->lineas[$i]['descripcion']       = null;

            // ✅ refrescar stock si no encontró producto
            $this->refreshStockLinea($i);

            $this->normalizeLinea($this->lineas[$i]);
            $this->syncProductosSeleccionados();
            $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
            $this->dispatch('$refresh');
            return;
        }

        // Cuenta ingreso sugerida por el producto/configuración
        $this->lineas[$i]['cuenta_ingreso_id'] = $this->resolveCuentaIngresoParaProducto($p);

        // Precio base
        $precioBase = 0.0;

        if (isset($p->precio) && !is_null($p->precio)) {
            $precioBase = (float) $p->precio;
        } elseif (isset($p->precio_venta) && !is_null($p->precio_venta)) {
            $precioBase = (float) $p->precio_venta;
        }

        // Impuesto sugerido
        $ivaPct = 0.0;
        $impId  = null;

        $imp = $p->impuesto;

        if ($imp && (int) ($imp->activo ?? 0) === 1) {
            $aplica = strtoupper((string) ($imp->aplica_sobre ?? ''));
            $aplicaVentas = in_array($aplica, ['VENTAS', 'VENTA', 'AMBOS', 'TODOS'], true);

            $hoy   = now()->startOfDay();
            $desde = $imp->vigente_desde ? Carbon::parse($imp->vigente_desde) : null;
            $hasta = $imp->vigente_hasta ? Carbon::parse($imp->vigente_hasta) : null;
            $vigente = (!$desde || $hoy->gte($desde)) && (!$hasta || $hoy->lte($hasta));

            if ($aplicaVentas && $vigente) {
                $impId = (int) $imp->id;

                if (!is_null($imp->porcentaje)) {
                    $ivaPct = (float) $imp->porcentaje;

                    if (!empty($imp->incluido_en_precio) && $ivaPct > 0) {
                        $precioBase = $precioBase > 0
                            ? round($precioBase / (1 + $ivaPct / 100), 2)
                            : 0.0;
                    }
                }
            }
        }

        $this->lineas[$i]['descripcion']     = (string) ($p->nombre ?? '');
        $this->lineas[$i]['precio_unitario'] = round($precioBase, 2);
        $this->lineas[$i]['impuesto_id']     = $impId;
        $this->lineas[$i]['impuesto_pct']    = $ivaPct;

        // ✅ ESTA ES LA PARTE CLAVE
        // si ya existe bodega predeterminada, aquí debe recalcularse el stock
        $this->refreshStockLinea($i);

        $this->normalizeLinea($this->lineas[$i]);
        $this->syncProductosSeleccionados();

        $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
        $this->dispatch('$refresh');
    } catch (\Throwable $e) {
        report($e);

        PendingToast::create()
            ->error()
            ->message(config('app.debug') ? $e->getMessage() : 'No se pudo establecer el producto.')
            ->duration(7000);
    }
}
    public function setImpuesto(int $i, $impuestoId): void
    {
        if ($this->bloqueada) return;

        if (!isset($this->lineas[$i])) return;

        $impId = $impuestoId ? (int)$impuestoId : null;
        $this->lineas[$i]['impuesto_id'] = $impId;

        if (!$impId) {
            $this->lineas[$i]['impuesto_pct'] = 0.0;
            $this->normalizeLinea($this->lineas[$i]);
            $this->dispatch('$refresh');
            return;
        }

        $imp = Impuesto::find($impId);
        if (!$imp || !$imp->activo) {
            $this->lineas[$i]['impuesto_pct'] = 0.0;
            $this->normalizeLinea($this->lineas[$i]);
            $this->dispatch('$refresh');
            return;
        }

        if (!is_null($imp->porcentaje)) {
            if ($imp->incluido_en_precio && $imp->porcentaje > 0) {
                $pu = (float)$this->lineas[$i]['precio_unitario'];
                $this->lineas[$i]['precio_unitario'] = $pu > 0 ? round($pu / (1 + $imp->porcentaje / 100), 2) : 0.0;
            }
            $this->lineas[$i]['impuesto_pct'] = (float)$imp->porcentaje;
        } else {
            $this->lineas[$i]['impuesto_pct'] = 0.0;
        }

        $this->normalizeLinea($this->lineas[$i]);
        $this->dispatch('$refresh');
    }

    public function aplicarFormaPago(string $tipo): void
    {
        if ($this->bloqueada) return;

        $this->tipo_pago = $tipo;

        // ✅ Auto-emitir SOLO si: es venta y contado
        $this->autoEmitirContado = ($this->modo === 'venta' && $tipo === 'contado');

        if ($tipo === 'contado') {
            $this->plazo_dias  = null;
            $this->vencimiento = $this->fecha;
        } else {
            if (!$this->plazo_dias) $this->plazo_dias = 30;
            $this->vencimiento = Carbon::parse($this->fecha)->addDays($this->plazo_dias)->toDateString();
        }
    }


    public function updatedFecha(): void
    {
        if ($this->bloqueada) return;
        $this->aplicarFormaPago($this->tipo_pago);
    }

    public function updatedPlazoDias(): void
    {
        if ($this->bloqueada) return;

        if ($this->tipo_pago === 'credito') {
            $d = max((int)$this->plazo_dias, 1);
            $this->plazo_dias  = $d;
            $this->vencimiento = Carbon::parse($this->fecha)->addDays($d)->toDateString();
            $this->terminos_pago = 'Crédito a ' . $d . ' días';
        }
    }

    private function setCuentaCobroPorDefecto(): void
    {
        if ($this->cuenta_cobro_id && PlanCuentas::whereKey($this->cuenta_cobro_id)->exists()) return;

        if ($this->socio_negocio_id) {
            $this->setCuentaDesdeCliente((int)$this->socio_negocio_id);
            return;
        }

        $this->cuenta_cobro_id =
            $this->idCuentaPorClase('CXC_CLIENTES')
            ?: $this->idCuentaPorClase('CAJA_GENERAL')
            ?: $this->idCuentaPorClase('BANCOS');

        $this->dispatch('$refresh');
    }

    private function setCuentaDesdeCliente(?int $clienteId): void
    {
        if ($this->bloqueada) return;
        if (!$clienteId) return;

        $socio = \App\Models\SocioNegocio\SocioNegocio::with('cuentas')->find($clienteId);
        $cxc = $socio?->cuentas?->cuenta_cxc_id ?? null;

        if ($cxc && PlanCuentas::whereKey($cxc)->exists()) {
            $this->cuenta_cobro_id = (int) $cxc;
        } else {
            $this->cuenta_cobro_id =
                $this->idCuentaPorClase('CXC_CLIENTES')
                ?: $this->idCuentaPorClase('CAJA_GENERAL')
                ?: $this->idCuentaPorClase('BANCOS');
        }

        $this->dispatch('$refresh');
    }

    private function idCuentaPorClase(string $clase): ?int
    {
        return PlanCuentas::query()
            ->where('clase_cuenta', $clase)
            ->where('cuenta_activa', 1)
            ->where(function ($q) {
                $q->where('titulo', 0)->orWhereNull('titulo');
            })
            ->value('id');
    }

    public function getSubtotalProperty(): float
    {
        $s = 0.0;

        foreach ($this->lineas as $l) {

            $cant   = (float)($l['cantidad'] ?? 0);
            $precio = max(0, (float)($l['precio_unitario'] ?? 0));
            $desc   = min(100, max(0, (float)($l['descuento_pct'] ?? 0)));

            // 👉 Si cantidad es null, vacío o 0, no suma
            if ($cant <= 0) {
                continue;
            }

            $base = $cant * $precio * (1 - $desc / 100);
            $s   += $base;
        }

        return round($s, 2);
    }
    public function getImpuestosTotalProperty(): float
    {
        $i = 0.0;

        foreach ($this->lineas as $l) {

            $cant   = (float)($l['cantidad'] ?? 0);
            $precio = max(0, (float)($l['precio_unitario'] ?? 0));
            $desc   = min(100, max(0, (float)($l['descuento_pct'] ?? 0)));
            $iva    = min(100, max(0, (float)($l['impuesto_pct'] ?? 0)));

            if ($cant <= 0) {
                continue;
            }

            $base = $cant * $precio * (1 - $desc / 100);
            $i   += $base * $iva / 100;
        }

        return round($i, 2);
    }
    public function getTotalProperty(): float
    {
        return round($this->subtotal + $this->impuestosTotal, 2);
    }

    private function normalizarPagoAntesDeValidar(): void
    {
        if ($this->tipo_pago === 'contado') {
            $this->plazo_dias    = null;
            $this->vencimiento   = $this->fecha;
            $this->terminos_pago = 'Contado';
        } else {
            $d = max((int)($this->plazo_dias ?: 30), 1);
            $this->plazo_dias    = $d;
            $this->vencimiento   = Carbon::parse($this->fecha)->addDays($d)->toDateString();
            $this->terminos_pago = 'Crédito a ' . $d . ' días';
        }
    }

    protected function persistirBorrador(): void
    {
        if ($this->bloqueada) {
            throw new \RuntimeException('La factura está bloqueada y no se puede modificar.');
        }

        DB::transaction(function () {
            $this->normalizarPagoAntesDeValidar();

            if (!$this->factura) {
                $this->factura = new Factura();
            }

            $esNueva = !$this->factura->exists;
            $uid = Auth::id();

            $serieId = (int) ($this->serie_id ?: $this->factura->serie_id ?: $this->serieDefault?->id ?: 0);
            $serie = $serieId ? Serie::find($serieId) : null;

            if (!$serie) {
                throw new \RuntimeException('Debes seleccionar una serie válida para guardar la factura.');
            }

            $dataCab = [
                'cotizacion_id'     => $this->cotizacion_id,
                'serie_id'          => $serie->id,
                'prefijo'           => $serie->prefijo ?: '',
                'socio_negocio_id'  => $this->socio_negocio_id,
                'fecha'             => $this->fecha,
                'vencimiento'       => $this->vencimiento,
                'moneda'            => $this->moneda,
                'tipo_pago'         => $this->tipo_pago,
                'plazo_dias'        => $this->plazo_dias,
                'terminos_pago'     => $this->terminos_pago,
                'notas'             => $this->notas,
                'estado'            => 'borrador',
                'cuenta_cobro_id'   => $this->cuenta_cobro_id,
                'condicion_pago_id' => $this->condicion_pago_id,
            ];

            if ($uid) {
                if (Schema::hasColumn('facturas', 'actualizado_por_id')) {
                    $dataCab['actualizado_por_id'] = $uid;
                }

                if ($esNueva && Schema::hasColumn('facturas', 'creado_por_id')) {
                    $dataCab['creado_por_id'] = $uid;
                }
            }

            \Illuminate\Database\Eloquent\Model::unguarded(function () use ($dataCab) {
                $this->factura->forceFill($dataCab)->save();
            });

            $this->factura->detalles()->delete();

            $detallesPayload = [];

            foreach ($this->lineas as $l) {
                $row = [
                    'producto_id'       => $l['producto_id'] ?? null,
                    'cuenta_ingreso_id' => isset($l['cuenta_ingreso_id']) ? (int) $l['cuenta_ingreso_id'] : null,
                    'bodega_id'         => isset($l['bodega_id']) ? (int) $l['bodega_id'] : null,
                    'descripcion'       => $l['descripcion'] ?? null,
                    'cantidad'          => (float) ($l['cantidad'] ?? 1),
                    'precio_unitario'   => (float) ($l['precio_unitario'] ?? 0),
                    'descuento_pct'     => (float) ($l['descuento_pct'] ?? 0),
                    'impuesto_id'       => $l['impuesto_id'] ?? null,
                    'impuesto_pct'      => (float) ($l['impuesto_pct'] ?? 0),
                ];

                if ($uid) {
                    if (Schema::hasColumn('factura_detalles', 'actualizado_por_id')) {
                        $row['actualizado_por_id'] = $uid;
                    }

                    if ($esNueva && Schema::hasColumn('factura_detalles', 'creado_por_id')) {
                        $row['creado_por_id'] = $uid;
                    }
                }

                $detallesPayload[] = $row;
            }

            if (!empty($detallesPayload)) {
                \Illuminate\Database\Eloquent\Model::unguarded(function () use ($detallesPayload) {
                    $this->factura->detalles()->createMany($detallesPayload);
                });
            }

            $this->factura->load('detalles');
            $this->factura->recalcularTotales()->save();

            $this->estado = $this->factura->estado;

            Log::info('Factura guardada (borrador)', [
                'factura_id'    => $this->factura->id,
                'cotizacion_id' => $this->cotizacion_id,
                'serie_id'      => $serie->id,
                'prefijo'       => $serie->prefijo,
                'detalles'      => $this->factura->detalles->count(),
            ]);
        }, 3);
    }

    public function buscarProductos(string $search = ''): array
    {
        $search = trim($search);

        $query = Producto::query()
            ->where('activo', 1)
            ->select(['id', 'nombre']);

        if (Schema::hasColumn('productos', 'codigo')) {
            $query->addSelect('codigo');
        }

        if (Schema::hasColumn('productos', 'ItemCode')) {
            $query->addSelect('ItemCode');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', '%' . $search . '%');

                if (Schema::hasColumn('productos', 'codigo')) {
                    $q->orWhere('codigo', 'like', '%' . $search . '%');
                }

                if (Schema::hasColumn('productos', 'ItemCode')) {
                    $q->orWhere('ItemCode', 'like', '%' . $search . '%');
                }
            });
        }

        $productos = $query
            ->orderBy('nombre')
            ->get();

        return $productos->map(function ($p) {
            $codigo = null;

            if (isset($p->ItemCode) && !empty($p->ItemCode)) {
                $codigo = $p->ItemCode;
            } elseif (isset($p->codigo) && !empty($p->codigo)) {
                $codigo = $p->codigo;
            }

            return [
                'id'   => (string) $p->id,
                'text' => $codigo ? ($codigo . ' - ' . $p->nombre) : $p->nombre,
            ];
        })->values()->toArray();
    }



    private function syncProductosSeleccionados(): void
    {
        $ids = collect($this->lineas)
            ->pluck('producto_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->productosSeleccionados = [];
            return;
        }

        $query = Producto::query()
            ->whereIn('id', $ids)
            ->select(['id', 'nombre']);

        if (Schema::hasColumn('productos', 'codigo')) {
            $query->addSelect('codigo');
        }

        if (Schema::hasColumn('productos', 'ItemCode')) {
            $query->addSelect('ItemCode');
        }

        $this->productosSeleccionados = $query
            ->get()
            ->mapWithKeys(function ($p) {
                $codigo = null;

                if (isset($p->ItemCode) && !empty($p->ItemCode)) {
                    $codigo = $p->ItemCode;
                } elseif (isset($p->codigo) && !empty($p->codigo)) {
                    $codigo = $p->codigo;
                }

                return [
                    (int) $p->id => $codigo ? ($codigo . ' - ' . $p->nombre) : $p->nombre,
                ];
            })
            ->toArray();
    }

    private function ensureCuentasEnLineas(): void
    {
        foreach ($this->lineas as $i => &$l) {
            if (empty($l['cuenta_ingreso_id']) && !empty($l['producto_id'])) {
                $p = Producto::with(['cuentas:id,producto_id,plan_cuentas_id,tipo_id'])->find($l['producto_id']);
                if ($p) $l['cuenta_ingreso_id'] = $this->resolveCuentaIngresoParaProducto($p);
            }
        }
    }

    private function validarConToast(): bool
    {
        try {
            $this->validate($this->rules, [], $this->validationAttributes);
            return true;
        } catch (ValidationException $e) {
            $first = collect($e->validator->errors()->all())->first() ?: 'Revisa los campos obligatorios.';
            PendingToast::create()->error()->message($first)->duration(9000);
            return false;
        }
    }

    private function stockDisponible(int $productoId, int $bodegaId): float
    {
        $stock = \App\Models\Productos\ProductoBodega::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->value('stock');

        return (float)($stock ?? 0);
    }

    private function nombreProducto(int $productoId): string
    {
        $query = Producto::query()->select(['id', 'nombre']);

        if (Schema::hasColumn('productos', 'codigo')) {
            $query->addSelect('codigo');
        }

        if (Schema::hasColumn('productos', 'ItemCode')) {
            $query->addSelect('ItemCode');
        }

        $p = $query->find($productoId);

        if (!$p) {
            return 'Producto #' . $productoId;
        }

        $codigo = null;

        if (isset($p->ItemCode) && !empty($p->ItemCode)) {
            $codigo = $p->ItemCode;
        } elseif (isset($p->codigo) && !empty($p->codigo)) {
            $codigo = $p->codigo;
        }

        return $codigo
            ? ($codigo . ' - ' . ($p->nombre ?? ''))
            : ($p->nombre ?? ('Producto #' . $productoId));
    }

    private function faltantesDeStock(): array
    {
        $faltantes = [];

        foreach ($this->lineas as $i => $l) {
            $pid = (int)($l['producto_id'] ?? 0);
            $bid = (int)($l['bodega_id'] ?? 0);
            $qty = (float)($l['cantidad'] ?? 0);

            if ($pid <= 0 || $bid <= 0 || $qty <= 0) {
                continue;
            }

            // 🔑 NUEVO: Verificar si el producto es inventariable
            $producto = Producto::find($pid);
            if (!$producto || !($producto->es_inventariable ?? true)) {
                $this->stockVista[$i] = 0; // No aplica stock
                continue;
            }

            $disponible = $this->stockDisponible($pid, $bid);
            $this->stockVista[$i] = (float)$disponible;

            if ($disponible + 1e-6 < $qty) {
                $faltantes[] = [
                    'index'      => $i,
                    'producto_id' => $pid,
                    'bodega_id'  => $bid,
                    'producto'   => $this->nombreProducto($pid),
                    'bodega'     => $this->nombreBodega($bid),
                    'pedido'     => round($qty, 3),
                    'disponible' => round($disponible, 3),
                    'faltante'   => round(max(0, $qty - $disponible), 3),
                ];
            }
        }

        $this->dispatch('$refresh');
        return $faltantes;
    }

    private function verificarStockParaLineasConDetalle(): array
    {
        try {
            $fake = $this->buildFakeFacturaFromLines();
            \App\Services\InventarioService::verificarDisponibilidadParaFactura($fake);
            return $this->faltantesDeStock();
        } catch (\Throwable $e) {
            return $this->faltantesDeStock();
        }
    }

    private function nombreBodega(int $bodegaId): string
    {
        $b = Bodega::query()->select('id', 'nombre', 'codigo')->find($bodegaId);
        if (!$b) return 'Bodega #' . $bodegaId;

        return ($b->codigo ? $b->codigo . ' - ' : '') . ($b->nombre ?? 'Bodega');
    }

    public function guardar(): void
    {
        if ($this->abortIfLocked('guardar')) return;

        try {
            $this->ensureCuentasEnLineas();
            $this->normalizarPagoAntesDeValidar();

            if (!$this->validarConToast()) return;

            // ✅ Solo advertencia de stock al guardar, no bloqueo
            try {
                InventarioService::verificarDisponibilidadParaFactura($this->buildFakeFacturaFromLines());
            } catch (\Throwable $ex) {
                PendingToast::create()
                    ->warning()
                    ->message('Advertencia de stock: ' . ($ex->getMessage() ?: 'verifica disponibilidad.'))
                    ->duration(9000);
            }

            // ✅ Siempre guardar como borrador, incluso si es contado
            $this->persistirBorrador();

            $this->factura->refresh()->recalcularTotales()->save();

            PendingToast::create()
                ->success()
                ->message(
                    $this->tipo_pago === 'contado'
                        ? 'Factura de contado guardada como borrador. Ahora puedes registrar el pago cuando desees.'
                        : 'Factura guardada como borrador.'
                )
                ->duration(5000);

            $this->dispatch('refrescar-lista-facturas');
        } catch (\Throwable $e) {
            Log::error('GUARDAR ERROR', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message(config('app.debug') ? $e->getMessage() : 'No se pudo guardar.')
                ->duration(9000);
        }
    }
    private function verificarStockParaLineas(): bool
    {
        try {
            $fake = $this->buildFakeFacturaFromLines();
            \App\Services\InventarioService::verificarDisponibilidadParaFactura($fake);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function emitir(): void
    {
        if ($this->abortIfLocked('emitir')) return;

        try {
            $this->ensureCuentasEnLineas();
            $this->normalizarPagoAntesDeValidar();

            if (!$this->validarConToast()) return;

            DB::transaction(function () {
                $this->persistirBorrador();

                $this->factura = Factura::with(['detalles', 'pagos', 'serie.tipo'])
                    ->findOrFail($this->factura->id);

                $this->factura->recalcularTotales()->save();
                $this->factura->refresh();

                $serie = $this->serie_id
                    ? Serie::find((int) $this->serie_id)
                    : $this->serieDefault;

                if (!$serie) {
                    throw new \RuntimeException('No hay una serie válida para emitir este documento.');
                }

                foreach ($this->factura->detalles as $idx => $d) {
                    if (empty($d->cuenta_ingreso_id)) {
                        throw new \RuntimeException("La fila #" . ($idx + 1) . " no tiene cuenta de ingreso.");
                    }

                    if (!$d->producto_id || !$d->bodega_id) {
                        throw new \RuntimeException("La fila #" . ($idx + 1) . " debe tener producto y bodega.");
                    }

                    if ((float) ($d->cantidad ?? 0) <= 0) {
                        throw new \RuntimeException("La fila #" . ($idx + 1) . " debe tener una cantidad mayor a cero.");
                    }
                }

                InventarioService::verificarDisponibilidadParaFactura($this->factura);

                if ($this->tipo_pago === 'contado') {
                    $this->factura->recalcularTotales()->save();
                    $this->factura->refresh();

                    $total    = round((float) ($this->factura->total ?? 0), 2);
                    $pagado   = round((float) $this->factura->pagos()->sum('monto'), 2);
                    $faltante = round($total - $pagado, 2);

                    if ($faltante > 0.01) {
                        throw new \RuntimeException('Para emitir una factura de contado, primero debes registrar el pago completo.');
                    }
                }

                // Si ya tiene número, no volver a emitir
                if (!empty($this->factura->numero)) {
                    $this->estado = (string) ($this->factura->estado ?? 'emitida');
                    return;
                }

                $numero = $serie->tomarConsecutivo();
                $uid = Auth::id();

                $dataUpdate = [
                    'serie_id' => $serie->id,
                    'prefijo'  => (string) ($serie->prefijo ?? ''),
                    'numero'   => $numero,
                    'estado'   => 'emitida',
                ];

                if (Schema::hasColumn('facturas', 'emitido_por_id')) {
                    $dataUpdate['emitido_por_id'] = $uid;
                }

                if (Schema::hasColumn('facturas', 'emitido_en')) {
                    $dataUpdate['emitido_en'] = now();
                }

                if (Schema::hasColumn('facturas', 'actualizado_por_id')) {
                    $dataUpdate['actualizado_por_id'] = $uid;
                }

                // ✅ Actualizar directamente en BD
                $updated = Factura::query()
                    ->whereKey($this->factura->id)
                    ->update($dataUpdate);

                if (!$updated) {
                    throw new \RuntimeException('No se pudo actualizar la factura con el consecutivo.');
                }

                // ✅ Recargar completamente desde BD
                $this->factura = Factura::with(['detalles', 'pagos', 'serie'])
                    ->findOrFail($this->factura->id);

                Log::info('FACTURA EMITIDA - POST UPDATE', [
                    'factura_id' => $this->factura->id,
                    'prefijo'    => $this->factura->prefijo,
                    'numero'     => $this->factura->numero,
                    'estado'     => $this->factura->estado,
                ]);

                if (is_null($this->factura->numero) || $this->factura->numero === '') {
                    throw new \RuntimeException('La factura se intentó emitir, pero el consecutivo no quedó guardado.');
                }

                ContabilidadService::asientoDesdeFactura($this->factura);
                InventarioService::descontarPorFactura($this->factura);

                $this->factura->recalcularTotales()->save();
                $this->factura->refresh();

                $this->estado = 'emitida';
            }, 3);

            $this->factura = Factura::with(['detalles', 'pagos', 'serie'])->findOrFail($this->factura->id);
            $this->factura->recalcularTotales()->save();
            $this->factura->refresh();

            $this->estado = (string) ($this->factura->estado ?? 'emitida');

            PendingToast::create()
                ->success()
                ->message(
                    'Factura emitida correctamente. No: ' .
                        (($this->factura->prefijo ?? '') !== '' ? $this->factura->prefijo . '-' : '') .
                        $this->factura->numero
                )
                ->duration(6000);

            $this->dispatch('refrescar-lista-facturas');
            $this->dispatch('$refresh');
        } catch (\Throwable $e) {
            Log::error('EMITIR ERROR', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'factura_id' => $this->factura->id ?? null,
                'serie_id'   => $this->serie_id ?? null,
            ]);

            PendingToast::create()
                ->error()
                ->message(
                    config('app.debug')
                        ? ($e->getMessage() ?: 'No se pudo emitir la factura.')
                        : 'No se pudo emitir la factura.'
                )
                ->duration(12000);
        }
    }

    public function anular(): void
    {
        if ($this->abortIfLocked('anular')) return;

        try {
            if (!$this->factura?->id) return;

            DB::transaction(function () {
                $this->factura->refresh()->loadMissing('detalles');

                if (in_array($this->factura->estado, ['emitida', 'cerrado'], true)) {
                    \App\Services\InventarioService::revertirPorFactura($this->factura);
                }

                $uid = Auth::id();

                $this->factura->update([
                    'estado'         => 'anulada',
                    'anulado_por_id' => $uid,
                    'anulado_en'     => now(),


                    'actualizado_por_id' => $uid,
                ]);
                $this->estado = 'anulada';
            }, 3);

            PendingToast::create()->info()->message('Factura anulada.')->duration(4500);
            $this->dispatch('refrescar-lista-facturas');
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo anular.')->duration(7000);
        }
    }


   public function abrirPagos(): void
{
    if ($this->abortIfLocked('registrar pagos')) return;

    try {
        if (!$this->verificarStockParaLineas()) {
            PendingToast::create()
                ->error()
                ->message('Hay faltante de stock en alguna línea. Ajusta cantidades o bodegas antes de registrar pagos.')
                ->duration(8000);
            return;
        }

        if (!$this->factura?->id) {
            $this->guardar();

            if (!$this->factura?->id) {
                return;
            }
        }

        $this->showPagos = true;
        $this->dispatch('preparar-modal-pago', facturaId: $this->factura->id);
    } catch (\Throwable $e) {
        $msg = trim((string) $e->getMessage());
        if ($msg === '') {
            $msg = 'Ocurrió un error inesperado.';
        }

        PendingToast::create()
            ->error()
            ->message('Error al abrir pagos: ' . $msg)
            ->duration(9000);
    }
}

    public function getProximoPreviewProperty(): ?string
    {
        try {
            $s = $this->factura?->serie_id ? Serie::find($this->factura->serie_id) : $this->serieDefault;
            if (!$s) return null;

            $n   = max((int)$s->proximo, (int)$s->desde);
            $len = $s->longitud ?? 6;
            $num = str_pad((string)$n, $len, '0', STR_PAD_LEFT);

            return ($s->prefijo ? "{$s->prefijo}-" : '') . $num;
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }

    public function onClienteChange($id): void
    {
        if ($this->bloqueada) return;
        $this->socio_negocio_id = (int) $id;
        $this->setCuentaDesdeCliente($this->socio_negocio_id);
        $this->setPagoDesdeCliente($this->socio_negocio_id);
    }

    private function condicionPagoDe(SocioNegocio $s): ?array
    {
        if (is_array($s->condiciones_pago_efectivas ?? null)) return $s->condiciones_pago_efectivas;
        if (is_array($s->condiciones_pago ?? null))         return $s->condiciones_pago;
        if (
            method_exists($s, 'condicionPago')
            && ($s->relationLoaded('condicionPago') ? $s->condicionPago : $s->loadMissing('condicionPago')->condicionPago)
        ) {
            $cp = $s->condicionPago;
            return [
                'id'                   => $cp->id,
                'nombre'               => $cp->nombre,
                'tipo'                 => $cp->tipo,
                'plazo_dias'           => $cp->plazo_dias,
                'interes_mora_pct'     => $cp->interes_mora_pct,
                'limite_credito'       => $cp->limite_credito,
                'tolerancia_mora_dias' => $cp->tolerancia_mora_dias,
                'dia_corte'            => $cp->dia_corte,
            ];
        }
        return null;
    }

    private function setPagoDesdeCliente(?int $clienteId): void
    {
        if ($this->bloqueada) return;
        if (!$clienteId) return;

        $socio = SocioNegocio::with('condicionPago')->find($clienteId);
        if (!$socio) return;

        $cp = $this->condicionPagoDe($socio);

        $tipo = 'contado';
        $plazo = null;
        if ($cp) {
            $raw = strtolower((string)($cp['tipo'] ?? $cp['tipo_credito'] ?? 'contado'));
            $tipo = (str_starts_with($raw, 'cred')) ? 'credito' : 'contado';
            $plazo = $tipo === 'credito' ? (int)($cp['plazo_dias'] ?? 30) : null;
        }

        $this->tipo_pago     = $tipo;
        $this->plazo_dias    = $plazo;
        $this->terminos_pago = $tipo === 'credito'
            ? 'Crédito a ' . (int)($this->plazo_dias ?: 30) . ' días'
            : 'Contado';

        $this->condicion_pago_id = $socio?->condicionPago?->id ?: null;

        // 🔽 clave: aplica forma y flag
        $this->aplicarFormaPago($this->tipo_pago);
        $this->dispatch('$refresh');
    }

    private function marcarPagadaSiAplica(): void
    {
        if (!$this->factura?->id) {
            return;
        }

        $this->factura = Factura::with(['pagos'])->findOrFail($this->factura->id);
        $this->factura->recalcularTotales()->save();
        $this->factura->refresh();

        $total  = round((float) ($this->factura->total ?? 0), 2);
        $pagado = round((float) $this->factura->pagos()->sum('monto'), 2);
        $falt   = round($total - $pagado, 2);

        if ($falt <= 0.01 && in_array($this->factura->estado, ['emitida', 'pagada'], true)) {
            $data = [
                'estado' => 'pagada',
            ];

            if (Schema::hasColumn('facturas', 'monto_aplicado')) {
                $data['monto_aplicado'] = $pagado;
            }

            if (Schema::hasColumn('facturas', 'pagado')) {
                $data['pagado'] = $pagado;
            }

            if (Schema::hasColumn('facturas', 'saldo')) {
                $data['saldo'] = 0;
            }

            $this->factura->update($data);
            $this->factura->refresh();

            $this->estado = 'pagada';

            PendingToast::create()
                ->success()
                ->message('Factura emitida y pagada completamente.')
                ->duration(5000);
        }
    }

    #[On('pago-registrado')]
    public function onPagoRegistrado(int $facturaId): void
    {
        try {
            $this->factura = Factura::with(['detalles', 'pagos', 'serie'])->findOrFail($facturaId);
            $this->factura->recalcularTotales()->save();
            $this->factura->refresh();

            // ✅ recarga visual desde BD, pero ya sin resetear precios
            $this->cargarFactura($facturaId);

            $this->estado    = (string) ($this->factura->estado ?? 'borrador');
            $this->tipo_pago = (string) ($this->factura->tipo_pago ?? $this->tipo_pago);

            $total    = round((float) ($this->factura->total ?? 0), 2);
            $pagado   = round((float) $this->factura->pagos()->sum('monto'), 2);
            $faltante = round($total - $pagado, 2);

            $esContado = ($this->factura->tipo_pago ?? '') === 'contado';
            $pagoTotal = ($faltante <= 0.01);

            $noEmitida = empty($this->factura->numero)
                && !in_array(($this->factura->estado ?? ''), ['emitida', 'pagada', 'anulada'], true);

            if ($esContado && $pagoTotal && $noEmitida) {
                $this->emitir();

                $this->factura = Factura::with(['detalles', 'pagos', 'serie'])->findOrFail($facturaId);
                $this->factura->recalcularTotales()->save();
                $this->factura->refresh();

                $this->cargarFactura($facturaId);
            }

            $this->marcarPagadaSiAplica();

            $this->factura = Factura::with(['detalles', 'pagos', 'serie'])->findOrFail($facturaId);
            $this->factura->recalcularTotales()->save();
            $this->factura->refresh();

            $this->estado = (string) ($this->factura->estado ?? 'borrador');

            $this->dispatch('refrescar-lista-facturas');
            $this->dispatch('$refresh');
        } catch (\Throwable $e) {
            Log::error('onPagoRegistrado error', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()->error()
                ->message('El pago se registró, pero no se pudo refrescar la factura.')
                ->duration(9000);
        }
    }



    private function verificarStockDisponibleAntesDeEmitir(): void
    {
        $factura = $this->factura?->loadMissing('detalles');
        if (!$factura || $factura->detalles->isEmpty()) {
            throw new \RuntimeException('No hay líneas para verificar stock.');
        }

        \App\Services\InventarioService::verificarDisponibilidadParaFactura($factura);
    }

    public function getStockDeLinea(int $i): float
    {
        return (float) ($this->stockVista[$i] ?? 0.0);
    }

    private function refreshStockLinea(int $i): void
    {
        if (!isset($this->lineas[$i])) return;

        $pid = (int) ($this->lineas[$i]['producto_id'] ?? 0);
        $bid = (int) ($this->lineas[$i]['bodega_id'] ?? 0);

        if ($pid <= 0 || $bid <= 0) {
            $this->stockVista[$i] = 0.0;
            $this->dispatch('$refresh');
            return;
        }

        // 🔑 NUEVO: Si no es inventariable, stock = 0 (no aplica)
        $producto = Producto::find($pid);
        if (!$producto || !($producto->es_inventariable ?? true)) {
            $this->stockVista[$i] = 0.0;
            $this->dispatch('$refresh');
            return;
        }

        $stock = \App\Models\Productos\ProductoBodega::query()
            ->where('producto_id', $pid)
            ->where('bodega_id', $bid)
            ->value('stock');

        $this->stockVista[$i] = (float) ($stock ?? 0);
        $this->dispatch('$refresh');
    }

    public function getDocumentoAttribute()
    {
        return "{$this->prefijo} {$this->numero}";
    }
    public function cargarDesdeCotizacion(int $cotizacionId): void
    {
        if ($this->bloqueada) {
            PendingToast::create()
                ->error()
                ->message('La factura está bloqueada y no puede cargarse una cotización.')
                ->duration(7000);
            return;
        }

        try {
            $cotizacion = CotizacionModel::with('detalles')->findOrFail($cotizacionId);

            $cotizacion = CotizacionModel::with('detalles')->findOrFail($cotizacionId);

            // Solo aviso informativo, pero NO bloquea
            $facturaExistente = Factura::query()
                ->where('cotizacion_id', $cotizacion->id)
                ->whereNotIn('estado', ['anulada'])
                ->orderByDesc('id')
                ->first();

            if ($facturaExistente && (!$this->factura || $this->factura->id !== $facturaExistente->id)) {
                PendingToast::create()
                    ->warning()
                    ->message("Esta cotización ya ha sido usada antes en la factura {$facturaExistente->prefijo} {$facturaExistente->numero}, pero puedes volver a relacionarla.")
                    ->duration(8000);
            }

            $this->cotizacion_id = (int) $cotizacion->id;
            $this->socio_negocio_id = (int) $cotizacion->socio_negocio_id;
            $this->fecha = now()->toDateString();
            $this->notas = $cotizacion->notas;
            $this->terminos_pago = $cotizacion->terminos_pago ?: 'Contado';
            $this->vencimiento = $cotizacion->vencimiento ?: $this->fecha;

            if ($this->socio_negocio_id) {
                $this->setCuentaDesdeCliente((int) $this->socio_negocio_id);
                $this->setPagoDesdeCliente((int) $this->socio_negocio_id);

                $socio = SocioNegocio::with('condicionPago')->find((int) $this->socio_negocio_id);
                $this->condicion_pago_id = $socio?->condicionPago?->id ?: null;
            }

            $this->lineas = [];
            $this->stockVista = [];

            foreach ($cotizacion->detalles as $d) {
                $producto = null;

                if (!empty($d->producto_id)) {
                    $producto = Producto::with([
                        'impuesto:id,nombre,porcentaje,monto_fijo,incluido_en_precio,aplica_sobre,activo,vigente_desde,vigente_hasta',
                        'cuentaIngreso:id,codigo,nombre',
                        'cuentas:id,producto_id,plan_cuentas_id,tipo_id',
                    ])->find($d->producto_id);
                }

                $cuentaIngresoId = null;
                $descripcion = null;
                $impuestoId = null;
                $impuestoPct = (float) ($d->impuesto_pct ?? 0);
                $precioUnitario = (float) ($d->precio_unitario ?? 0);

                if ($producto) {
                    $cuentaIngresoId = $this->resolveCuentaIngresoParaProducto($producto);
                    $descripcion = $producto->nombre ?: 'Producto';

                    if ($producto->impuesto && (int) ($producto->impuesto->activo ?? 0) === 1) {
                        $imp = $producto->impuesto;

                        $aplica = strtoupper((string) ($imp->aplica_sobre ?? ''));
                        $aplicaVentas = in_array($aplica, ['VENTAS', 'VENTA', 'AMBOS', 'TODOS'], true);

                        $hoy = now()->startOfDay();
                        $desde = $imp->vigente_desde ? Carbon::parse($imp->vigente_desde) : null;
                        $hasta = $imp->vigente_hasta ? Carbon::parse($imp->vigente_hasta) : null;
                        $vigente = (!$desde || $hoy->gte($desde)) && (!$hasta || $hoy->lte($hasta));

                        if ($aplicaVentas && $vigente) {
                            $impuestoId = (int) $imp->id;
                            $impuestoPct = !is_null($imp->porcentaje) ? (float) $imp->porcentaje : 0.0;
                        }
                    }
                }

                $linea = [
                    'producto_id'       => !empty($d->producto_id) ? (int) $d->producto_id : null,
                    'cuenta_ingreso_id' => $cuentaIngresoId,
                    'bodega_id'         => !empty($d->bodega_id)
                        ? (int) $d->bodega_id
                        : $this->bodega_predeterminada_empresa_id,
                    'descripcion'       => $descripcion ?: 'Producto importado desde cotización',
                    'cantidad'          => is_null($d->cantidad) ? null : (float) $d->cantidad,
                    'precio_unitario'   => $precioUnitario,
                    'descuento_pct'     => (float) ($d->descuento_pct ?? 0),
                    'impuesto_id'       => $impuestoId,
                    'impuesto_pct'      => (float) $impuestoPct,
                ];

                $this->normalizeLinea($linea);
                $this->lineas[] = $linea;
            }

            if (empty($this->lineas)) {
                $this->addLinea();
            }

            foreach ($this->lineas as $i => $linea) {
                $this->refreshStockLinea($i);
            }

            $this->resetErrorBag();
            $this->resetValidation();
            $this->takeSnapshot();

            $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
            $this->dispatch('sync-cotizacion-tomselect', cotizacionId: $this->cotizacion_id);
            $this->dispatch('$refresh');


            PendingToast::create()
                ->success()
                ->message('Cotización #' . $cotizacion->id . ' cargada correctamente en la factura.')
                ->duration(5000);
        } catch (\Throwable $e) {
            report($e);

            PendingToast::create()
                ->error()
                ->message(config('app.debug') ? $e->getMessage() : 'No se pudo cargar la cotización.')
                ->duration(9000);
        }
    }



    /** Factura “fake” solo para validar stock con las líneas actuales. */
    /** Factura "fake" solo para validar stock con las líneas actuales. */
    private function buildFakeFacturaFromLines(): \App\Models\Factura\Factura
    {
        $fake = $this->factura ?: new \App\Models\Factura\Factura();

        // 🔑 NUEVO: Filtrar solo productos inventariables
        $fake->setRelation('detalles', collect($this->lineas)->map(function ($l) {
            $pid = $l['producto_id'] ?? null;

            // Solo incluir si es inventariable
            if ($pid) {
                $producto = Producto::find($pid);
                if (!$producto || !($producto->es_inventariable ?? true)) {
                    return null; // Excluir de validación
                }
            }

            return (object)[
                'producto_id' => $pid,
                'bodega_id'   => $l['bodega_id'] ?? null,
                'cantidad'    => (float)($l['cantidad'] ?? 0),
            ];
        })->filter()); // Eliminar nulls

        return $fake;
    }


    private function resetFormulario(): void
    {
        $this->factura = null;
        $this->cotizacion_id = null;
        $this->serie_id = $this->serieDefault?->id;
        $this->socio_negocio_id = null;
        $this->fecha = now()->toDateString();
        $this->vencimiento = null;
        $this->tipo_pago = 'contado';
        $this->plazo_dias = null;
        $this->terminos_pago = 'Contado';
        $this->notas = null;
        $this->moneda = 'COP';
        $this->estado = 'borrador';
        $this->lineas = [];
        $this->stockVista = [];
        $this->cuenta_cobro_id = null;
        $this->condicion_pago_id = null;
        $this->autoEmitirContado = true;
        $this->habilitarActualizar = false;
        $this->originalHash = null;

        $this->addLinea();
        $this->setCuentaCobroPorDefecto();

        $this->dispatch('$refresh');

        PendingToast::create()
            ->info()
            ->message('Formulario reiniciado, listo para nueva factura.')
            ->duration(4000);
    }
    /** Hash estable del estado relevante del form */
    private function computeHash(): string
    {
        $payload = [
            'cotizacion_id'     => (int) ($this->cotizacion_id ?? 0),
            'serie_id'          => (int) ($this->serie_id ?? 0),
            'socio_negocio_id'  => (int) ($this->socio_negocio_id ?? 0),
            'fecha'             => (string) $this->fecha,
            'vencimiento'       => (string) ($this->vencimiento ?? ''),
            'tipo_pago'         => (string) $this->tipo_pago,
            'plazo_dias'        => (int) ($this->plazo_dias ?? 0),
            'terminos_pago'     => (string) ($this->terminos_pago ?? ''),
            'notas'             => (string) ($this->notas ?? ''),
            'moneda'            => (string) $this->moneda,
            'estado'            => (string) $this->estado,
            'cuenta_cobro_id'   => (int) ($this->cuenta_cobro_id ?? 0),
            'condicion_pago_id' => (int) ($this->condicion_pago_id ?? 0),
            'lineas'            => array_values(array_map(function ($l) {
                return [
                    'producto_id'       => (int) ($l['producto_id'] ?? 0),
                    'cuenta_ingreso_id' => (int) ($l['cuenta_ingreso_id'] ?? 0),
                    'bodega_id'         => (int) ($l['bodega_id'] ?? 0),
                    'descripcion'       => (string) ($l['descripcion'] ?? ''),
                    'cantidad'          => round((float) ($l['cantidad'] ?? 0), 3),
                    'precio_unitario'   => round((float) ($l['precio_unitario'] ?? 0), 2),
                    'descuento_pct'     => round((float) ($l['descuento_pct'] ?? 0), 3),
                    'impuesto_id'       => (int) ($l['impuesto_id'] ?? 0),
                    'impuesto_pct'      => round((float) ($l['impuesto_pct'] ?? 0), 3),
                ];
            }, $this->lineas ?? [])),
        ];

        return hash('sha256', json_encode($payload));
    }
    /** Congela el snapshot actual como base “sin cambios” */
    private function takeSnapshot(): void
    {
        $this->originalHash = $this->computeHash();
        $this->habilitarActualizar = false;
    }

    /** Recalcula la bandera de cambios, respetando reglas de estado */
    private function markDirtyIfNeeded(): void
    {
        // No habilitar actualización si está bloqueada o emitida
        if ($this->bloqueada || $this->estado === 'emitida') {
            $this->habilitarActualizar = false;
            return;
        }
        $this->habilitarActualizar = $this->computeHash() !== ($this->originalHash ?? '');
    }
    public function actualizar(): void
    {
        if ($this->estado === 'emitida' || $this->bloqueada) {
            PendingToast::create()->error()->message('No es posible actualizar: la factura está bloqueada o emitida.')->duration(6000);
            return;
        }

        try {
            // Reusa tu flujo de borrador
            $this->persistirBorrador();
            $this->takeSnapshot();

            PendingToast::create()->success()->message('Cambios actualizados correctamente.')->duration(4500);
            $this->dispatch('refrescar-lista-facturas');
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudieron actualizar los cambios.')->duration(8000);
        }
    }

    public function cargarCotizacionSeleccionada(): void
    {
        if (!$this->cotizacion_id) {
            PendingToast::create()
                ->error()
                ->message('Debes seleccionar una cotización.')
                ->duration(5000);
            return;
        }

        $this->cargarDesdeCotizacion((int) $this->cotizacion_id);
    }
}
