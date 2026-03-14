<?php

namespace App\Livewire\Facturas\FacturaCompra;

use App\Models\Bodega;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Factura\Factura;
use App\Models\Impuestos\Impuesto;
use App\Models\Productos\Producto;
use App\Models\Productos\ProductoCuentaTipo;
use App\Models\Serie\Serie as SerieModel;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\TiposDocumento\TipoDocumento;
use App\Models\CondicionPago\CondicionPago;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\PendingToast;
use Throwable;

class FacturaCompra extends Component
{
    public ?Factura $factura = null;

  public string $documento = 'facturacompra';
    public ?SerieModel $serieDefault = null;
    public ?int $stockCheck = null;
    public string $modo = 'compra';

    // Cabecera
    public ?int $serie_id = null;
    public ?int $socio_negocio_id = null;
    public string $fecha = '';
    public ?string $vencimiento = null;
    public ?string $notas = null;
    public string $moneda = 'COP';
    public string $estado = 'borrador';
    public ?int $cuenta_cobro_id = null;   // CxP

    // Condición de pago
    public ?int $condicion_pago_id = null;
    public ?int $plazo_dias = null;

    // Detalles
    public array $lineas = [];
    public array $stockVista = [];

    // Catálogo PUC para compras (inventario/gasto) + índice
    public $cuentasInventario;
    public array $pucIndex = [];

    protected $rules = [
        'serie_id'                       => 'required|integer|exists:series,id',
        'socio_negocio_id'               => 'required|integer|exists:socio_negocios,id',
        'fecha'                          => 'required|date',
        'vencimiento'                    => 'required|date|after_or_equal:fecha',
        'moneda'                         => 'required|string|size:3',
        'cuenta_cobro_id'                => 'required|integer|exists:plan_cuentas,id',

        'condicion_pago_id'              => 'required|integer|exists:condicion_pagos,id',
        'plazo_dias'                     => 'nullable|integer|min:0',

        'lineas'                         => 'required|array|min:1',
        'lineas.*.producto_id'           => 'required|integer|exists:productos,id',
        'lineas.*.cuenta_inventario_id'  => 'nullable|integer|exists:plan_cuentas,id', // 🔑 CAMBIO: nullable
        'lineas.*.bodega_id'             => 'required|integer|exists:bodegas,id',
        'lineas.*.descripcion'           => 'nullable|string|max:255',
        'lineas.*.cantidad'              => 'required|numeric|min:1',
        'lineas.*.precio_unitario'       => 'required|numeric|gt:0',
        'lineas.*.descuento_pct'         => 'required|numeric|min:0|max:100',
        'lineas.*.impuesto_id'           => 'nullable|integer|exists:impuestos,id',
        'lineas.*.impuesto_pct'          => 'required|numeric|min:0|max:100',
    ];

    protected array $validationAttributes = [
        'serie_id'                       => 'serie',
        'socio_negocio_id'               => 'proveedor',
        'vencimiento'                    => 'vencimiento',
        'cuenta_cobro_id'                => 'cuenta por pagar (CxP)',
        'condicion_pago_id'              => 'condición de pago',
        'plazo_dias'                     => 'plazo (días)',
        'lineas'                         => 'líneas',
        'lineas.*.producto_id'           => 'producto',
        'lineas.*.cuenta_inventario_id'  => 'cuenta de inventario/gasto',
        'lineas.*.bodega_id'             => 'bodega',
        'lineas.*.descripcion'           => 'descripción',
        'lineas.*.cantidad'              => 'cantidad',
        'lineas.*.precio_unitario'       => 'precio unitario',
        'lineas.*.descuento_pct'         => 'descuento (%)',
        'lineas.*.impuesto_id'           => 'indicador de impuesto',
        'lineas.*.impuesto_pct'          => 'porcentaje de impuesto',
    ];

    #[On('abrir-factura')]
    public function abrir(int $id): void
    {
        $this->cargarFactura($id);
    }

    /* ======================
     *  Inicialización
     * ====================== */
   public function mount(?int $id = null): void
{
    $this->fecha = now()->toDateString();

    // ✅ Igual que venta: documento fijo para compras
    $this->documento = 'facturacompra';
    $this->serieDefault = SerieModel::defaultParaCodigo($this->documento);

    // Catálogo PUC (1/5/6) e índice (inventario/gasto/costo)
    $this->cuentasInventario = PlanCuentas::query()
        ->where('cuenta_activa', 1)
        ->where(fn($q) => $q->whereNull('titulo')->orWhere('titulo', 0))
        ->where(function ($q) {
            $q->where('codigo', 'like', '1%')
                ->orWhere('codigo', 'like', '5%')
                ->orWhere('codigo', 'like', '6%');
        })
        ->orderBy('codigo')
        ->get(['id', 'codigo', 'nombre']);

    $this->pucIndex = $this->cuentasInventario
        ->keyBy('id')
        ->map(fn($c) => ['codigo' => $c->codigo, 'nombre' => $c->nombre])
        ->toArray();

    if ($id) {
        $this->cargarFactura($id);

        foreach ($this->lineas as &$l) {
            if (empty($l['cuenta_inventario_id']) && !empty($l['cuenta_ingreso_id'])) {
                $l['cuenta_inventario_id'] = (int) $l['cuenta_ingreso_id'];
            }
        }
        unset($l);

        if (!$this->factura?->serie_id && $this->serieDefault) {
            $this->serie_id = $this->serieDefault->id;
        } else {
            $this->serie_id = $this->factura?->serie_id ?: $this->serieDefault?->id;
        }
    } else {
        $this->addLinea();
        $last = array_key_last($this->lineas);

        if ($last !== null && !array_key_exists('cuenta_inventario_id', $this->lineas[$last])) {
            $this->lineas[$last]['cuenta_inventario_id'] = null;
        }

        $this->serie_id = $this->serieDefault?->id;
    }

    $this->stockCheck = 0;

    $this->setCuentaCobroPorDefecto();

    if (!$this->condicion_pago_id) {
        $contado = CondicionPago::where('plazo_dias', 0)->value('id');
        $this->condicion_pago_id = $contado ?: optional(
            CondicionPago::orderBy('plazo_dias')->first()
        )->id;
    }

    $this->syncCondicionAndDueDate();

    if ($this->socio_negocio_id) {
        $this->setCuentaDesdeProveedor((int) $this->socio_negocio_id);
        $this->setCondicionDesdeProveedor((int) $this->socio_negocio_id);
    }
}
    private function detectarCodigoDocumento(): ?string
    {
        if ($this->factura?->relationLoaded('serie') && $this->factura->serie?->relationLoaded('tipo') && $this->factura->serie->tipo?->codigo) {
            return (string) $this->factura->serie->tipo->codigo;
        }

        if ($this->serieDefault?->relationLoaded('tipo') && $this->serieDefault->tipo?->codigo) {
            return (string) $this->serieDefault->tipo->codigo;
        }

        $base = class_basename(static::class);
        $slug = preg_replace('/[^a-z0-9]+/i', '', $base);
        if ($slug) return strtoupper($slug);

        return TipoDocumento::orderBy('id')->value('codigo');
    }

    /** En este componente siempre es COMPRA */
    private function aplicaSobreParaImpuestos(): array
    {
        return ['COMPRAS', 'COMPRA', 'AMBOS', 'TODOS'];
    }

    /* ======================
     *  Render
     * ====================== */
    public function render()
    {
        try {
            try {
                $clientes = SocioNegocio::proveedores()
                    ->orderBy('razon_serial')->orderBy('razon_social')
                    ->take(200)->get();
            } catch (\Throwable $e) {
                $clientes = SocioNegocio::proveedores()
                    ->orderBy('razon_social')->take(200)->get();
            }

            $productos = Producto::with([
                'impuesto:id,nombre,porcentaje,monto_fijo,incluido_en_precio,aplica_sobre,activo,vigente_desde,vigente_hasta,cuenta_id',
                'cuentaIngreso:id,codigo,nombre',
                'cuentas:id,producto_id,plan_cuentas_id,tipo_id',
                'cuentas.cuentaPUC:id,codigo,nombre',
                'cuentas.tipo:id,codigo,nombre',
                'subcategoria:id,nombre',
                'subcategoria.cuentas:id,subcategoria_id,tipo_id,plan_cuentas_id',
            ])
                ->where('activo', 1)
                ->orderBy('nombre')
                ->get();
            $bodegas = Bodega::orderBy('nombre')->get();

            // Listado de cuentas imputables (si necesitas mostrar más de inventario)
            $cuentasIngresos = PlanCuentas::query()
                ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
                ->where('cuenta_activa', 1)
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre']);

            $impuestosDocumento = Impuesto::activos()
                ->whereIn('aplica_sobre', $this->aplicaSobreParaImpuestos())
                ->orderBy('prioridad')
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'porcentaje', 'monto_fijo', 'incluido_en_precio']);

            $series = SerieModel::query()
                ->with('tipo')
                ->activa()
                ->when(
                    $this->documento !== '',
                    fn($q) => $q->whereHas('tipo', fn($t) => $t->where('codigo', $this->documento))
                )
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'prefijo', 'desde', 'hasta', 'proximo', 'longitud', 'es_default', 'tipo_documento_id']);

            if (!$this->serie_id) {
                $this->serie_id = $this->serieDefault?->id
                    ?? optional($series->firstWhere('es_default', true))->id
                    ?? optional($series->first())->id;
            }

            // Cuentas proveedor (CxP/IVA si aplica)
            $cuentasProveedor = ['todas' => collect()];
            if ($this->socio_negocio_id) {
                $cuentasProveedor = $this->obtenerCuentasProveedor((int)$this->socio_negocio_id);
            }

            // Condiciones de pago
            $condicionesPago = CondicionPago::orderBy('nombre')
                ->get(['id', 'nombre', DB::raw('plazo_dias as dias')]);

            return view('livewire.facturas.factura-compra.factura-compra', [
                'clientes'          => $clientes,
                'productos'         => $productos,
                'bodegas'           => $bodegas,
                'series'            => $series,
                'serieDefault'      => $this->serieDefault,
                'cuentasIngresos'   => $cuentasIngresos,
                'cuentasInventario' => $this->cuentasInventario,
                'impuestosVentas'   => $impuestosDocumento,
                'bloqueada'         => $this->bloqueada,
                'cuentasProveedor'  => $cuentasProveedor,
                'condicionesPago'   => $condicionesPago,
            ]);
        } catch (Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo cargar datos auxiliares.')->duration(6000);

            return view('livewire.facturas.factura-compra.factura-compra', [
                'clientes'          => collect(),
                'productos'         => collect(),
                'bodegas'           => collect(),
                'series'            => collect(),
                'serieDefault'      => $this->serieDefault,
                'cuentasIngresos'   => collect(),
                'cuentasInventario' => $this->cuentasInventario ?? collect(),
                'impuestosVentas'   => collect(),
                'bloqueada'         => $this->bloqueada,
                'cuentasProveedor'  => ['todas' => collect()],
                'condicionesPago'   => collect(),
            ]);
        }
    }

    /* =========================
     *  BLOQUEO / SOLO LECTURA
     * ========================= */
 public function getBloqueadaProperty(): bool
{
    $estado = strtolower((string) ($this->factura->estado ?? $this->estado ?? 'borrador'));

    return in_array($estado, ['emitida', 'cerrado', 'anulada'], true);
}

 private function abortIfLocked(string $accion = 'editar'): bool
{
    if ($this->bloqueada) {
        $estado = strtolower((string) ($this->factura->estado ?? $this->estado ?? 'bloqueada'));
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
        return cache()->remember(
            'producto_cuenta_tipo_ingreso_id',
            600,
            fn() =>
            ProductoCuentaTipo::query()->where('codigo', 'INGRESO')->value('id')
        );
    }

    private function resolveCuentaIngresoParaProducto(Producto $p): ?int
    {
        if (!empty($p->cuenta_ingreso_id)) return (int) $p->cuenta_ingreso_id;

        $tipoId = $this->tipoIngresoId();
        if (!$tipoId) return null;

        if ($p->mov_contable_segun === Producto::MOV_SEGUN_ARTICULO) {
            $cuenta = $p->relationLoaded('cuentas')
                ? $p->cuentas->firstWhere('tipo_id', (int)$tipoId)
                : $p->cuentas()->where('tipo_id', (int)$tipoId)->first();

            return $cuenta?->plan_cuentas_id ? (int)$cuenta->plan_cuentas_id : null;
        }

        if ($p->mov_contable_segun === Producto::MOV_SEGUN_SUBCATEGORIA) {
            if (!$p->subcategoria_id) return null;

            if ($p->relationLoaded('subcategoria') && $p->subcategoria?->relationLoaded('cuentas')) {
                $sc = $p->subcategoria->cuentas->firstWhere('tipo_id', (int)$tipoId);
                return $sc?->plan_cuentas_id ? (int)$sc->plan_cuentas_id : null;
            }

            $sc = \App\Models\Categorias\SubcategoriaCuenta::query()
                ->where('subcategoria_id', (int)$p->subcategoria_id)
                ->where('tipo_id', (int)$tipoId)
                ->first();

            return $sc?->plan_cuentas_id ? (int)$sc->plan_cuentas_id : null;
        }

        return null;
    }

    /** Resolver cuenta INVENTARIO por artículo/subcategoría */
    private function resolveCuentaInventarioParaProducto(Producto $p): ?int
    {
        // Si tiene cuenta directa en el producto
        if (!empty($p->cuenta_inventario_id)) return (int)$p->cuenta_inventario_id;

        // Orden de prioridad según TU base de datos
        $tiposInventario = ['INVENTARIO', 'COSTO'];

        foreach ($tiposInventario as $codigoTipo) {
            $tipoId = cache()->remember(
                "producto_cuenta_tipo_{$codigoTipo}_id",
                600,
                fn() =>
                ProductoCuentaTipo::query()->where('codigo', $codigoTipo)->value('id')
            );

            if (!$tipoId) continue;

            $cuenta = $this->resolverCuentaPorTipo($p, (int)$tipoId);
            if ($cuenta) return $cuenta;
        }

        return null;
    }

    private function resolveCuentaGastoParaProducto(Producto $p): ?int
    {
        // Orden de prioridad según TU base de datos
        $tiposGasto = ['GASTO', 'COSTO'];

        foreach ($tiposGasto as $codigoTipo) {
            $tipoId = cache()->remember(
                "producto_cuenta_tipo_{$codigoTipo}_id",
                600,
                fn() =>
                ProductoCuentaTipo::query()->where('codigo', $codigoTipo)->value('id')
            );

            if (!$tipoId) continue;

            $cuenta = $this->resolverCuentaPorTipo($p, (int)$tipoId);
            if ($cuenta) return $cuenta;
        }

        return null;
    }

    /**
     * 🔑 Helper: Resuelve cuenta por tipo según MOV_SEGUN (ARTICULO o SUBCATEGORIA)
     */
    private function resolverCuentaPorTipo(Producto $p, int $tipoId): ?int
    {
        // Estrategia 1: Por ARTÍCULO (busca en producto_cuentas)
        if ($p->mov_contable_segun === Producto::MOV_SEGUN_ARTICULO) {
            // Intentar con relaciones cargadas
            if ($p->relationLoaded('cuentas')) {
                $cuenta = $p->cuentas->firstWhere('tipo_id', $tipoId);
                if ($cuenta && $cuenta->plan_cuentas_id) {
                    return (int)$cuenta->plan_cuentas_id;
                }
            }

            // Consulta directa si no está cargado
            $cuenta = \App\Models\Productos\ProductoCuenta::query()
                ->where('producto_id', $p->id)
                ->where('tipo_id', $tipoId)
                ->first();

            return $cuenta?->plan_cuentas_id ? (int)$cuenta->plan_cuentas_id : null;
        }

        // Estrategia 2: Por SUBCATEGORÍA (busca en subcategoria_cuentas)
        if ($p->mov_contable_segun === Producto::MOV_SEGUN_SUBCATEGORIA) {
            if (!$p->subcategoria_id) return null;

            // Intentar con relaciones cargadas
            if ($p->relationLoaded('subcategoria') && $p->subcategoria?->relationLoaded('cuentas')) {
                $sc = $p->subcategoria->cuentas->firstWhere('tipo_id', $tipoId);
                if ($sc && $sc->plan_cuentas_id) {
                    return (int)$sc->plan_cuentas_id;
                }
            }

            // Consulta directa si no está cargado
            $sc = \App\Models\Categorias\SubcategoriaCuenta::query()
                ->where('subcategoria_id', (int)$p->subcategoria_id)
                ->where('tipo_id', $tipoId)
                ->first();

            return $sc?->plan_cuentas_id ? (int)$sc->plan_cuentas_id : null;
        }

        return null;
    }

    private function normalizeLinea(array &$l): void
    {
        if (isset($l['costo_unitario']) && (!isset($l['precio_unitario']) || (float)$l['precio_unitario'] <= 0)) {
            $l['precio_unitario'] = (float)$l['costo_unitario'];
        }

        $cant   = (float)($l['cantidad'] ?? 0);
        $precio = (float)($l['precio_unitario'] ?? 0);
        $desc   = (float)($l['descuento_pct'] ?? 0);
        $iva    = (float)($l['impuesto_pct'] ?? 0);


        $l['cantidad']        = max(0.0, round(is_finite($cant)   ? $cant   : 0, 3));
        $l['precio_unitario'] = max(0.0, round(is_finite($precio) ? $precio : 0, 2));
        $l['descuento_pct']   = min(100.0, max(0.0, round(is_finite($desc) ? $desc : 0, 3)));
        $l['impuesto_pct']    = min(100.0, max(0.0, round(is_finite($iva)  ? $iva  : 0, 3)));
    }

    public function normalizarPrecio(int $i): void
    {
        if (!isset($this->lineas[$i]) || $this->bloqueada) return;
        $this->lineas[$i]['precio_unitario'] = max(0.0, (float)($this->lineas[$i]['precio_unitario'] ?? 0));
        $this->normalizeLinea($this->lineas[$i]);
        $this->dispatch('$refresh');
    }


    public function normalizarCantidad(int $i): void
    {
        if ($this->bloqueada) return;
        if (!isset($this->lineas[$i])) return;

        $raw = $this->lineas[$i]['cantidad'] ?? null;

        // ✅ si está vacío, se queda vacío
        if ($raw === '' || $raw === null) {
            $this->lineas[$i]['cantidad'] = null;
            $this->normalizeLinea($this->lineas[$i]);
            $this->dispatch('$refresh');
            return;
        }

        $cant = (float) $raw;

        // ✅ si es inválido o negativo → 0 (no 1)
        if (!is_finite($cant) || $cant < 0) $cant = 0;

        $this->lineas[$i]['cantidad'] = round($cant, 3);
        $this->normalizeLinea($this->lineas[$i]);
        $this->dispatch('$refresh');
    }

    public function updated($name, $value): void
    {
        if ($this->bloqueada) return;

        if (preg_match('/^lineas\.(\d+)\.producto_id$/', $name, $m)) {
            $i = (int) $m[1];
            $this->stockCheck = $i;
            $this->setProducto($i, $value);
            $this->refreshStockLinea($i);
            $this->resetErrorBag();
            $this->resetValidation();
            $this->dispatch('$refresh');
            return;
        }

        if (preg_match('/^lineas\.(\d+)\.bodega_id$/', $name, $m)) {
            $i = (int) $m[1];
            $this->stockCheck = $i;
            $this->refreshStockLinea($i);
            return;
        }

        if (preg_match('/^lineas\.(\d+)\.(descuento_pct|impuesto_pct)$/', $name, $m)) {
            $i = (int) $m[1];
            $this->stockCheck = $i;
            if (isset($this->lineas[$i])) {
                $this->normalizeLinea($this->lineas[$i]);
                $this->dispatch('$refresh');
            }
            return;
        }
    }

    // Proveedor: set CxP + Condición
    public function updatedSocioNegocioId($val): void
    {
        if ($this->bloqueada) return;

        $id = (int) $val;

        if ($id > 0) {
            $this->setCuentaDesdeProveedor($id);
            $this->setCondicionDesdeProveedor($id);
        } else {
            $this->cuenta_cobro_id = null;
        }

        $this->dispatch('$refresh');
    }

    public function updatedCondicionPagoId($val): void
    {
        if ($this->bloqueada) return;

        $keepCxP = $this->cuenta_cobro_id;
        $this->condicion_pago_id = $val ? (int)$val : null;
        $this->syncCondicionAndDueDate();
        $this->cuenta_cobro_id = $keepCxP;
    }

    public function updatedFecha($val): void
    {
        if ($this->bloqueada) return;
        $base = (string)$val ?: now()->toDateString();
        $this->vencimiento = $this->calcularVencimiento($base, $this->plazo_dias);
        $this->dispatch('$refresh');
    }

    private function cargarFactura(int $id): void
    {
        try {
            $f = Factura::with(['detalles', 'serie.tipo'])->findOrFail($id);
            $this->factura = $f;

            $this->fill($f->only([
                'serie_id',
                'socio_negocio_id',
                'fecha',
                'vencimiento',
                'notas',
                'moneda',
                'estado',
                'cuenta_cobro_id',
                'condicion_pago_id',
                'plazo_dias',
            ]));

            $this->syncCondicionAndDueDate();

            $this->lineas = $f->detalles->map(function ($d) {
                $cuentaId = $d->cuenta_inventario_id ? (int)$d->cuenta_inventario_id : null;

                if (!$cuentaId && $d->producto_id) {
                    $p = Producto::with([
                        'cuentas:id,producto_id,plan_cuentas_id,tipo_id',
                        'subcategoria.cuentas:id,subcategoria_id,tipo_id,plan_cuentas_id',
                    ])->find($d->producto_id);
                    if ($p) {
                        // Usar lógica según tipo
                        $cuentaId = ($p->es_inventariable ?? true)
                            ? $this->resolveCuentaInventarioParaProducto($p)
                            : $this->resolveCuentaGastoParaProducto($p);
                    }
                }

                $l = [
                    'id'                     => $d->id,
                    'producto_id'            => $d->producto_id,
                    'cuenta_inventario_id'   => $cuentaId,
                    'bodega_id'              => $d->bodega_id,
                    'descripcion'            => $d->descripcion,
                    'cantidad'               => (float)$d->cantidad,
                    'precio_unitario'        => (float)$d->precio_unitario,
                    'descuento_pct'          => (float)$d->descuento_pct,
                    'impuesto_id'            => $d->impuesto_id ?? null,
                    'impuesto_pct'           => (float)$d->impuesto_pct,
                ];
                $this->normalizeLinea($l);
                return $l;
            })->toArray();

            $this->resetErrorBag();
            $this->resetValidation();
        } catch (Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo cargar la factura.')->duration(7000);
        }
    }

    public function addLinea(): void
    {
        if ($this->bloqueada) return;

        $l = [
            'producto_id'           => null,
            'cuenta_inventario_id'  => null,
            'bodega_id'             => null,
            'descripcion'           => null,
            'cantidad'              => null,
            'precio_unitario'       => 0,
            'descuento_pct'         => 0,
            'impuesto_id'           => null,
            'impuesto_pct'          => 0,
        ];

        $this->normalizeLinea($l);
        $this->lineas[] = $l;

        $this->stockCheck = array_key_last($this->lineas) ?? 0;

        $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
    }

    public function removeLinea(int $i): void
    {
        if ($this->bloqueada) return;
        if (!isset($this->lineas[$i])) return;

        array_splice($this->lineas, $i, 1);
        $this->stockCheck = max(0, min((int) $this->stockCheck, count($this->lineas) - 1));

        $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
    }

    public function setProducto(int $i, $id): void
    {
        if ($this->bloqueada) return;
        try {
            if (!isset($this->lineas[$i])) return;

            $prodId = $id ? (int) $id : null;
            $this->lineas[$i]['producto_id'] = $prodId;

            if (!$prodId) {
                $this->lineas[$i]['cuenta_inventario_id'] = null;
                $this->lineas[$i]['precio_unitario']      = 0.0;
                $this->lineas[$i]['impuesto_id']          = null;
                $this->lineas[$i]['impuesto_pct']         = 0.0;
                $this->normalizeLinea($this->lineas[$i]);
                $this->dispatch('$refresh');
                return;
            }

            $p = Producto::with([
                'impuesto:id,nombre,porcentaje,monto_fijo,incluido_en_precio,aplica_sobre,activo,vigente_desde,vigente_hasta,cuenta_id',
                'cuentaIngreso:id',
                'cuentas:id,producto_id,plan_cuentas_id,tipo_id',
                'subcategoria:id,nombre',
                'subcategoria.cuentas:id,subcategoria_id,tipo_id,plan_cuentas_id',
            ])->find($prodId);

            if (!$p) {
                $this->lineas[$i]['cuenta_inventario_id'] = null;
                $this->lineas[$i]['precio_unitario']      = 0.0;
                $this->lineas[$i]['impuesto_id']          = null;
                $this->lineas[$i]['impuesto_pct']         = 0.0;
                $this->normalizeLinea($this->lineas[$i]);
                $this->dispatch('$refresh');
                return;
            }

            // 🔑 NUEVO: Sugerir cuenta según tipo de producto
            if ($p->es_inventariable ?? true) {
                // Producto físico → cuenta INVENTARIO
                $this->lineas[$i]['cuenta_inventario_id'] = $this->resolveCuentaInventarioParaProducto($p);
            } else {
                // Servicio → cuenta GASTO
                $this->lineas[$i]['cuenta_inventario_id'] = $this->resolveCuentaGastoParaProducto($p);
            }

            // Precio base/costeo
            $precioBase = (float)($this->lineas[$i]['precio_unitario'] ?? 0);

            if ($precioBase <= 0) {
                if ($this->modo === 'compra') {
                    $bid = (int)($this->lineas[$i]['bodega_id'] ?? 0);
                    if ($bid > 0) {
                        $pb = \App\Models\Productos\ProductoBodega::query()
                            ->where('producto_id', $p->id)
                            ->where('bodega_id', $bid)
                            ->first();
                        if ($pb) {
                            $precioBase = (float)($pb->ultimo_costo ?? 0.0);
                            if ($precioBase <= 0) {
                                $precioBase = (float)($pb->costo_promedio ?? 0.0);
                            }
                        }
                    }
                    if ($precioBase <= 0 && isset($p->costo)) {
                        $precioBase = (float)$p->costo;
                    }
                } else {
                    $precioBase = (float) ($p->precio ?? $p->precio_venta ?? 0.0);
                }
            }

            // Impuesto sugerido (sin desinflar en compras)
            $ivaPct  = 0.0;
            $impId   = null;
            $imp     = $p->impuesto;

            if ($imp && (int)($imp->activo ?? 0) === 1) {
                $aplica   = strtoupper((string)($imp->aplica_sobre ?? ''));
                $aplicaOK = in_array($aplica, $this->aplicaSobreParaImpuestos(), true);

                $hoy   = now()->startOfDay();
                $desde = $imp->vigente_desde ? \Carbon\Carbon::parse($imp->vigente_desde) : null;
                $hasta = $imp->vigente_hasta ? \Carbon\Carbon::parse($imp->vigente_hasta) : null;
                $vigente = (!$desde || $hoy->gte($desde)) && (!$hasta || $hoy->lte($hasta));

                if ($aplicaOK && $vigente) {
                    $impId = (int)$imp->id;

                    if (!is_null($imp->porcentaje)) {
                        $ivaPct = (float)$imp->porcentaje;
                        if ($this->modo !== 'compra' && !empty($imp->incluido_en_precio) && $ivaPct > 0) {
                            $precioBase = $precioBase > 0 ? round($precioBase / (1 + $ivaPct / 100), 2) : 0.0;
                        }
                    } else {
                        $ivaPct = 0.0;
                    }
                }
            }

            if (empty($this->lineas[$i]['descripcion'])) {
                $this->lineas[$i]['descripcion'] = (string) $p->nombre;
            }

            $this->lineas[$i]['precio_unitario'] = $precioBase;
            $this->lineas[$i]['impuesto_id']     = $impId;
            $this->lineas[$i]['impuesto_pct']    = $ivaPct;

            $this->normalizeLinea($this->lineas[$i]);
            $this->dispatch('$refresh');
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo establecer el producto.')->duration(5000);
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
            if ($this->modo !== 'compra' && $imp->incluido_en_precio && $imp->porcentaje > 0) {
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

    private function setCuentaCobroPorDefecto(): void
    {
        if ($this->cuenta_cobro_id && PlanCuentas::whereKey($this->cuenta_cobro_id)->exists()) return;

        if ($this->socio_negocio_id) {
            $this->setCuentaDesdeProveedor((int)$this->socio_negocio_id);
            return;
        }

        $this->cuenta_cobro_id = null;
        $this->dispatch('$refresh');
    }

    private function idCuentaPorClase(string $clase): ?int
    {
        return PlanCuentas::query()
            ->where('clase_cuenta', $clase)
            ->where('cuenta_activa', 1)
            ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
            ->value('id');
    }

    private function obtenerCuentasProveedor(?int $proveedorId): array
    {
        $vacio = [
            'cxp'        => null,
            'anticipos'  => null,
            'desc'       => null,
            'ret_fuente' => null,
            'ret_ica'    => null,
            'iva'        => null,
            'todas'      => collect(),
        ];
        if (!$proveedorId) return $vacio;

        $socio = SocioNegocio::with('cuentas')->find($proveedorId);
        if (!$socio) return $vacio;

        $c = $socio->cuentas;

        $cxp = $c?->cuenta_cxp_id ?? $c?->cuenta_cxc_id ?? null;

        $anticipos  = $c?->cuenta_anticipos_id   ?? null;
        $desc       = $c?->cuenta_descuentos_id  ?? null;
        $ret_fuente = $c?->cuenta_ret_fuente_id  ?? null;
        $ret_ica    = $c?->cuenta_ret_ica_id     ?? null;
        $iva        = $c?->cuenta_iva_compras_id ?? $c?->cuenta_iva_id ?? null;

        $ids = collect([$cxp, $anticipos, $desc, $ret_fuente, $ret_ica, $iva])->filter()->values();

        $todas = $ids->isEmpty() ? collect() : PlanCuentas::query()
            ->whereIn('id', $ids)
            ->where('cuenta_activa', 1)
            ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
            ->get(['id', 'codigo', 'nombre']);

        $pick = fn($id) => $id ? $todas->firstWhere('id', (int)$id) : null;

        return [
            'cxp'        => $pick($cxp),
            'anticipos'  => $pick($anticipos),
            'desc'       => $pick($desc),
            'ret_fuente' => $pick($ret_fuente),
            'ret_ica'    => $pick($ret_ica),
            'iva'        => $pick($iva),
            'todas'      => $todas,
        ];
    }

    private function setCuentaDesdeProveedor(?int $proveedorId): void
    {
        if ($this->bloqueada || !$proveedorId) return;

        $prov = $this->obtenerCuentasProveedor($proveedorId);

        if ($prov['cxp']) {
            $this->cuenta_cobro_id = (int)$prov['cxp']->id;
            $this->dispatch('$refresh');
            return;
        }

        $this->cuenta_cobro_id =
            $this->idCuentaPorClase('CXP_PROVEEDORES')
            ?: $this->idCuentaPorClase('BANCOS')
            ?: $this->idCuentaPorClase('CAJA_GENERAL');

        $this->dispatch('$refresh');
    }

    private function setCondicionDesdeProveedor(int $proveedorId): void
    {
        try {
            $prov = SocioNegocio::select('id', 'condicion_pago_id')->find($proveedorId);
            if ($prov && $prov->condicion_pago_id) {
                $this->condicion_pago_id = (int)$prov->condicion_pago_id;
                $this->syncCondicionAndDueDate();
            }
        } catch (\Throwable $e) {
            // silencioso
        }
    }

    private function calcularVencimiento(string $fecha, ?int $dias): string
    {
        $d = \Carbon\Carbon::parse($fecha ?: now()->toDateString());
        $dias = max(0, (int)($dias ?? 0));
        return $d->copy()->addDays($dias)->toDateString();
    }

    private function syncCondicionAndDueDate(): void
    {
        $dias = 0;
        if ($this->condicion_pago_id) {
            $dias = (int) CondicionPago::whereKey($this->condicion_pago_id)->value('plazo_dias');
        }
        $this->plazo_dias = $dias;
        $base = $this->fecha ?: now()->toDateString();
        $this->vencimiento = $this->calcularVencimiento($base, $dias);
        $this->resetErrorBag(['vencimiento', 'condicion_pago_id', 'plazo_dias']);
        $this->dispatch('$refresh');
    }

    public function getSubtotalProperty(): float
    {
        $s = 0.0;

        foreach ($this->lineas as $l) {
            $cant   = (float)($l['cantidad'] ?? 0);
            $precio = max(0, (float)($l['precio_unitario'] ?? 0));
            $desc   = min(100, max(0, (float)($l['descuento_pct'] ?? 0)));

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



    #[On('set-producto-linea')]
    public function setProductoLinea($index, $productoId = null): void
    {
        if ($this->bloqueada) return;

        $i = (int) $index;
        $pid = $productoId ? (int) $productoId : null;

        if (!isset($this->lineas[$i])) {
            return;
        }

        $this->stockCheck = $i;
        $this->lineas[$i]['producto_id'] = $pid;

        $this->setProducto($i, $pid);
        $this->refreshStockLinea($i);

        $this->resetErrorBag();
        $this->resetValidation();

        $this->dispatch('sync-productos-tomselect', lineas: $this->lineas);
    }

    private function getSerieActual(): ?SerieModel
    {
        $serieId = $this->serie_id ?: $this->factura?->serie_id ?: $this->serieDefault?->id;

        if (!$serieId) {
            return null;
        }

        return SerieModel::find($serieId);
    }


    protected function persistirBorrador(): void
    {
        if ($this->bloqueada) {
            throw new \RuntimeException('La factura está bloqueada y no se puede modificar.');
        }

        DB::transaction(function () {
            $usuarioId = Auth::id();

            if (!$this->factura) {
                $this->factura = new Factura();
            }

         $esNueva = !$this->factura->exists;

            $serieActual = $this->getSerieActual();

            if (!$serieActual) {
                throw new \RuntimeException('No hay una serie válida seleccionada.');
            }

            $dataCab = [
                'serie_id'            => $serieActual->id,
                'prefijo'             => $serieActual->prefijo ?: '',
                'socio_negocio_id'    => $this->socio_negocio_id,
                'fecha'               => $this->fecha,
                'vencimiento'         => $this->vencimiento ?? $this->fecha,
                'moneda'              => $this->moneda,
                'notas'               => $this->notas,
                'estado'              => 'borrador',
                'cuenta_cobro_id'     => $this->cuenta_cobro_id,
                'condicion_pago_id'   => $this->condicion_pago_id,
                'plazo_dias'          => $this->plazo_dias,
                'actualizado_por_id'  => $usuarioId,
            ];

            if ($esNueva) {
                $dataCab['creado_por_id'] = $usuarioId;
            }

            \Illuminate\Database\Eloquent\Model::unguarded(function () use ($dataCab) {
                $this->factura->forceFill($dataCab)->save();
            });

            $this->factura->detalles()->delete();

            $detallesPayload = [];

            foreach ($this->lineas as $l) {
                $cantidad = ($l['cantidad'] ?? null);

                $cantidad = ($cantidad === '' || $cantidad === null || !is_numeric($cantidad))
                    ? 0
                    : (float) $cantidad;

                $detallesPayload[] = [
                    'producto_id'          => $l['producto_id'] ?? null,
                    'cuenta_inventario_id' => isset($l['cuenta_inventario_id']) ? (int) $l['cuenta_inventario_id'] : null,
                    'bodega_id'            => isset($l['bodega_id']) ? (int) $l['bodega_id'] : null,
                    'descripcion'          => $l['descripcion'] ?? null,
                    'cantidad'             => $cantidad,
                    'precio_unitario'      => (float) ($l['precio_unitario'] ?? $l['costo_unitario'] ?? 0),
                    'descuento_pct'        => (float) ($l['descuento_pct'] ?? 0),
                    'impuesto_id'          => $l['impuesto_id'] ?? null,
                    'impuesto_pct'         => (float) ($l['impuesto_pct'] ?? 0),
                ];
            }

            if (!empty($detallesPayload)) {
                \Illuminate\Database\Eloquent\Model::unguarded(function () use ($detallesPayload) {
                    $this->factura->detalles()->createMany($detallesPayload);
                });
            }

            $this->factura->load('detalles');
            $this->factura->recalcularTotales()->save();

            $this->estado = $this->factura->estado;

            Log::info('Factura compra guardada (borrador)', [
                'factura_id' => $this->factura->id,
                'detalles'   => $this->factura->detalles->count(),
                'usuario_id' => $usuarioId,
            ]);
        }, 3);
    }

    private function ensureCuentasEnLineas(): void
    {
        foreach ($this->lineas as $i => &$l) {
            if (empty($l['cuenta_inventario_id']) && !empty($l['producto_id'])) {
                $p = Producto::with([
                    'cuentas:id,producto_id,plan_cuentas_id,tipo_id',
                    'subcategoria.cuentas:id,subcategoria_id,tipo_id,plan_cuentas_id',
                ])->find($l['producto_id']);

                if ($p) {
                    // Usar lógica según tipo
                    $l['cuenta_inventario_id'] = ($p->es_inventariable ?? true)
                        ? $this->resolveCuentaInventarioParaProducto($p)
                        : $this->resolveCuentaGastoParaProducto($p);
                }
            }
        }
        unset($l);
    }

    private function validarConToast(): bool
    {
        try {
            // 🔑 NUEVO: Validación personalizada para cuenta_inventario_id
            foreach ($this->lineas as $i => $l) {
                if (empty($l['producto_id'])) continue;

                $p = Producto::find($l['producto_id']);
                if (!$p) continue;

                // Si es inventariable, debe tener cuenta
                if (($p->es_inventariable ?? true) && empty($l['cuenta_inventario_id'])) {
                    throw ValidationException::withMessages([
                        "lineas.{$i}.cuenta_inventario_id" => 'Los productos inventariables requieren cuenta de inventario/gasto.'
                    ]);
                }
            }

            $this->validate($this->rules, [], $this->validationAttributes);
            return true;
        } catch (ValidationException $e) {
            $first = collect($e->validator->errors()->all())->first() ?: 'Revisa los campos obligatorios.';
            PendingToast::create()->error()->message($first)->duration(9000);
            return false;
        }
    }

    public function guardar(): void
    {
        if ($this->abortIfLocked('guardar')) return;

        try {
            $this->ensureCuentasEnLineas();
            if (!$this->validarConToast()) return;

            $this->persistirBorrador();

            PendingToast::create()->success()->message('Factura guardada (ID: ' . $this->factura->id . ').')->duration(5000);
            $this->dispatch('refrescar-lista-facturas');
        } catch (\Throwable $e) {
            Log::error('GUARDAR ERROR', ['msg' => $e->getMessage()]);
            PendingToast::create()->error()->message(config('app.debug') ? $e->getMessage() : 'No se pudo guardar.')->duration(9000);
        }
    }

public function emitir(): void
{
    if ($this->abortIfLocked('emitir')) return;

    try {
        $this->ensureCuentasEnLineas();

        if (!$this->validarConToast()) return;

        DB::transaction(function () {
            $this->persistirBorrador();

            $this->factura = Factura::with(['detalles', 'serie.tipo'])
                ->findOrFail($this->factura->id);

            $this->factura->recalcularTotales()->save();
            $this->factura->refresh();

            $serieActual = $this->serie_id
                ? SerieModel::find((int) $this->serie_id)
                : $this->serieDefault;

            if (!$serieActual) {
                throw new \RuntimeException('No hay una serie válida para emitir la factura de compra.');
            }

            // Si ya tiene número, no volver a emitir
            if (!empty($this->factura->numero)) {
                $this->estado = (string) ($this->factura->estado ?? 'emitida');
                return;
            }

            $numero = $serieActual->tomarConsecutivo();

            $updated = Factura::query()
                ->whereKey($this->factura->id)
                ->update([
                    'serie_id'           => $serieActual->id,
                    'prefijo'            => (string) ($serieActual->prefijo ?? ''),
                    'numero'             => $numero,
                    'estado'             => 'emitida',
                    'emitido_por_id'     => Auth::id(),
                    'emitido_en'         => now(),
                    'actualizado_por_id' => Auth::id(),
                ]);

            if (!$updated) {
                throw new \RuntimeException('No se pudo actualizar la factura con el consecutivo.');
            }

            $this->factura = Factura::with(['detalles', 'serie'])
                ->findOrFail($this->factura->id);

            if (is_null($this->factura->numero) || $this->factura->numero === '') {
                throw new \RuntimeException('La factura se intentó emitir, pero el número no quedó guardado.');
            }

            \App\Services\FacturaCompraService::asientoDesdeFacturaCompra($this->factura);
            InventarioService::aumentarPorFacturaCompra($this->factura);

            $this->factura->recalcularTotales()->save();
            $this->factura->refresh();

            $this->estado = 'emitida';
            $this->serie_id = $this->factura->serie_id;
        }, 3);

        PendingToast::create()->success()->message(
            'Factura emitida correctamente. No: ' .
                (($this->factura->prefijo ?? '') !== '' ? $this->factura->prefijo . '-' : '') .
                $this->factura->numero
        )->duration(6000);

        $this->dispatch('refrescar-lista-facturas');
        $this->dispatch('$refresh');

        // ⚠️ temporalmente NO resetees el formulario hasta verificar
        // $this->resetFormulario();

    } catch (\Throwable $e) {
        Log::error('EMITIR ERROR FACTURA COMPRA', [
            'msg' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'factura_id' => $this->factura->id ?? null,
            'serie_id'   => $this->serie_id ?? null,
        ]);

        PendingToast::create()->error()->message(
            config('app.debug') ? $e->getMessage() : 'No se pudo emitir la factura de compra.'
        )->duration(12000);
    }
}

    public function validarAntesDeEmitir(): void
    {
        try {
            if (!$this->factura?->id) {
                $this->persistirBorrador();
            }
            $this->factura->refresh()->loadMissing('detalles');

            $errores = \App\Services\FacturaCompraService::validarFacturaCompraParaAsiento($this->factura);
            if (empty($errores)) {
                PendingToast::create()->success()->message('Listo para emitir.')->duration(5000);
            } else {
                PendingToast::create()->error()->message(implode(' | ', $errores))->duration(12000);
            }
        } catch (\Throwable $e) {
            PendingToast::create()->error()->message($e->getMessage())->duration(12000);
        }
    }

    public function onClienteChange($id): void
    {
        if ($this->bloqueada) return;

        $pid = (int) $id;

        $this->socio_negocio_id = $pid;

        if ($pid > 0) {
            $this->setCuentaDesdeProveedor($pid);
            $this->setCondicionDesdeProveedor($pid);
        } else {
            $this->cuenta_cobro_id    = null;
            $this->condicion_pago_id  = null;
            $this->plazo_dias         = 0;
            $this->syncCondicionAndDueDate();
        }

        $this->dispatch('$refresh');
    }

   public function getProximoPreviewProperty(): ?string
{
    try {
        $s = $this->getSerieActual();

        if (!$s) {
            return null;
        }

        $n   = max((int) $s->proximo, (int) $s->desde);
        $len = $s->longitud ?? 6;
        $num = str_pad((string) $n, $len, '0', STR_PAD_LEFT);

        return ($s->prefijo ? "{$s->prefijo}-" : '') . $num;
    } catch (Throwable $e) {
        report($e);
        return null;
    }
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

        $stock = \App\Models\Productos\ProductoBodega::query()
            ->where('producto_id', $pid)
            ->where('bodega_id', $bid)
            ->value('stock');

        $this->stockVista[$i] = (float) ($stock ?? 0);
        $this->dispatch('$refresh');
    }

    private function resetFormulario(): void
{
    $this->factura = null;

    $this->documento = 'facturacompra';
    $this->serieDefault = SerieModel::defaultParaCodigo($this->documento);
    $this->serie_id = $this->serieDefault?->id;

    $this->socio_negocio_id = null;
    $this->fecha = now()->toDateString();
    $this->vencimiento = null;
    $this->notas = null;
    $this->moneda = 'COP';
    $this->estado = 'borrador';
    $this->lineas = [];
    $this->stockVista = [];
    $this->cuenta_cobro_id = null;
    $this->plazo_dias = null;

    $contado = CondicionPago::where('plazo_dias', 0)->value('id');
    $this->condicion_pago_id = $contado ?: optional(
        CondicionPago::orderBy('plazo_dias')->first()
    )->id;

    $this->syncCondicionAndDueDate();

    $this->addLinea();

    $this->dispatch('$refresh');

    PendingToast::create()->info()->message('Formulario reiniciado, listo para nueva factura.')->duration(4000);
}
}
