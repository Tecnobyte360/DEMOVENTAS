<?php

namespace App\Livewire\Facturas;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

use App\Models\Serie\Serie;
use App\Models\Factura\Factura;

use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Productos\Producto;
use App\Models\Bodegas;
use App\Models\CondicionPago\CondicionPago;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Productos\ProductoCuentaTipo;
use App\Models\Impuestos\Impuesto;
use App\Models\NotaCredito;
use App\Services\ContabilidadNotaCreditoService;
use App\Services\InventarioService;
use Masmerise\Toaster\PendingToast;

class NotaCreditoForm extends Component
{
    public ?NotaCredito $nota = null;
    public string $documento = 'nota_credito';
    public ?Serie $serieDefault = null;

    public ?int $serie_id = null;
    public ?int $socio_negocio_id = null;
    public ?int $factura_id = null;
    public string $fecha = '';
    public ?string $vencimiento = null;
    public string $tipo_pago = 'credito';
    public ?int $plazo_dias = null;
    public ?string $terminos_pago = null;
    public ?string $notas = null;
    public string $moneda = 'COP';

    public string $estado = 'borrador';
    public array $lineas = [];
    public array $stockVista = [];
    public ?string $motivo = null;
    public ?string $numeroFacturaSeleccionada = null;
    public ?int $cuenta_cobro_id = null;
    public ?int $condicion_pago_id = null;

    protected $rules = [
        'serie_id'                   => 'required|integer|exists:series,id',
        'socio_negocio_id'           => 'required|integer|exists:socio_negocios,id',
        'fecha'                      => 'required|date',
        'vencimiento'                => 'nullable|date|after_or_equal:fecha',
        'tipo_pago'                  => 'required|in:contado,credito',
        'plazo_dias'                 => 'nullable|integer|min:1|max:365|required_if:tipo_pago,credito',
        'terminos_pago'              => 'nullable|string|max:255',
        'moneda'                     => 'required|string|size:3',
        'cuenta_cobro_id'            => 'required|integer|exists:plan_cuentas,id',
        'condicion_pago_id'          => 'nullable|integer|exists:condicion_pagos,id',
        'factura_id'                 => 'nullable|integer|exists:facturas,id',

        'lineas'                     => 'required|array|min:1',
        'lineas.*.producto_id'       => 'required|integer|exists:productos,id',
        'lineas.*.cuenta_ingreso_id' => 'nullable|integer|exists:plan_cuentas,id',
        'lineas.*.bodega_id'         => 'required|integer|exists:bodegas,id',
        'lineas.*.descripcion'       => 'required|string|max:255',
        'lineas.*.cantidad'          => 'required|numeric|min:1',
        'lineas.*.precio_unitario'   => 'required|numeric|min:0',
        'lineas.*.descuento_pct'     => 'required|numeric|min:0|max:100',
        'lineas.*.impuesto_id'       => 'nullable|integer|exists:impuestos,id',
        'lineas.*.impuesto_pct'      => 'required|numeric|min:0|max:100',
    ];

    protected array $validationAttributes = [
        'serie_id' => 'serie',
        'socio_negocio_id' => 'cliente',
        'vencimiento' => 'vencimiento',
        'plazo_dias' => 'plazo en días',
        'terminos_pago' => 'términos',
        'cuenta_cobro_id' => 'cuenta para cobrar del cliente',
        'condicion_pago_id' => 'condición de pago',
        'factura_id' => 'factura origen',
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

    #[On('abrir-nota-credito')]
    public function abrir(int $id): void
    {
        $this->cargarNota($id);
    }

    public function mount(?int $id = null, ?int $factura_id = null): void
    {
        try {
            $this->fecha = now()->toDateString();
            $this->serieDefault = Serie::defaultPara($this->documento);
            $this->serie_id = $this->serieDefault?->id;

            if ($id) {
                $this->cargarNota($id);
            } else {
                $this->addLinea();
                $this->terminos_pago = 'Nota crédito';
                if ($factura_id) {
                    $this->factura_id = $factura_id;
                    $this->precargarDesdeFactura($factura_id);
                }
            }

            $this->setCuentaCobroPorDefecto();
        } catch (Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo inicializar la nota crédito.')->duration(7000);
        }
    }

    public function render()
    {
        try {
            $clientes = SocioNegocio::clientes()->orderBy('razon_social')->take(200)->get();

            $productos = Producto::with([
                'impuesto:id,nombre,porcentaje,monto_fijo,incluido_en_precio,aplica_sobre,activo,vigente_desde,vigente_hasta',
                'cuentaIngreso:id,codigo,nombre',
                'cuentas:id,producto_id,plan_cuentas_id,tipo_id',
                'cuentas.cuentaPUC:id,codigo,nombre',
                'cuentas.tipo:id,codigo,nombre',
            ])->where('activo', 1)->orderBy('nombre')->take(300)->get();

            $bodegas = Bodegas::orderBy('nombre')->get();

            $cuentasIngresos = PlanCuentas::query()
                ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
                ->where('cuenta_activa', 1)
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre']);

            $cuentasCXC = PlanCuentas::where('cuenta_activa', 1)->where('titulo', 0)
                ->where('clase_cuenta', 'CXC_CLIENTES')
                ->orderBy('codigo')->get(['id', 'codigo', 'nombre']);

            $cuentasCaja = PlanCuentas::where('cuenta_activa', 1)->where('titulo', 0)
                ->whereIn('clase_cuenta', ['CAJA_GENERAL', 'BANCOS', 'CAJA'])
                ->orderBy('codigo')->get(['id', 'codigo', 'nombre']);

            $impuestosVentas = Impuesto::activos()
                ->whereIn('aplica_sobre', ['VENTAS','VENTA','AMBOS','TODOS'])
                ->orderBy('prioridad')
                ->orderBy('nombre')
                ->get(['id','codigo','nombre','porcentaje','monto_fijo','incluido_en_precio']);

            $condicionesPago = CondicionPago::query()
                ->orderBy('nombre')
                ->get(['id','nombre','tipo','plazo_dias']);

            $facturasCliente = collect();
            if ($this->socio_negocio_id) {
                $facturasCliente = Factura::query()
                    ->where('socio_negocio_id', (int)$this->socio_negocio_id)
                    ->whereIn('estado', ['emitida','cerrado'])
                    ->orderByDesc('fecha')
                    ->orderByDesc('id')
                    ->limit(100)
                    ->get(['id','prefijo','numero','fecha','total']);
            }

            return view('livewire.facturas.nota-credito-form', [
                'clientes'         => $clientes,
                'productos'        => $productos,
                'bodegas'          => $bodegas,
                'series'           => $this->serieDefault ? collect([$this->serieDefault]) : collect(),
                'serieDefault'     => $this->serieDefault,
                'cuentasIngresos'  => $cuentasIngresos,
                'cuentasCXC'       => $cuentasCXC,
                'cuentasCaja'      => $cuentasCaja,
                'impuestosVentas'  => $impuestosVentas,
                'bloqueada'        => $this->bloqueada,
                'condicionesPago'  => $condicionesPago,
                'facturasCliente'  => $facturasCliente,
            ]);
        } catch (Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo cargar datos auxiliares.')->duration(6000);

            return view('livewire.facturas.nota-credito-form', [
                'clientes'         => collect(),
                'productos'        => collect(),
                'bodegas'          => collect(),
                'series'           => collect(),
                'serieDefault'     => $this->serieDefault,
                'cuentasIngresos'  => collect(),
                'cuentasCXC'       => collect(),
                'cuentasCaja'      => collect(),
                'impuestosVentas'  => collect(),
                'bloqueada'        => $this->bloqueada,
                'condicionesPago'  => collect(),
                'facturasCliente'  => collect(),
            ]);
        }
    }

    /* ===== BLOQUEO / SOLO LECTURA ===== */

    public function getBloqueadaProperty(): bool
    {
        $estado = $this->nota->estado ?? $this->estado ?? 'borrador';
        return in_array($estado, ['cerrado', 'anulada'], true);
    }

    private function abortIfLocked(string $accion = 'editar'): bool
    {
        if ($this->bloqueada) {
            PendingToast::create()
                ->warning()->message("La nota está {$this->estado}; no se puede {$accion}.")
                ->duration(7000);
            return true;
        }
        return false;
    }

    /* ===== HELPERS ===== */

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
        if (!empty($p->cuenta_ingreso_id)) {
            return (int) $p->cuenta_ingreso_id;
        }
        $tipoId = $this->tipoIngresoId();
        if ($tipoId) {
            $cuenta = $p->relationLoaded('cuentas')
                ? $p->cuentas->firstWhere('tipo_id', (int)$tipoId)
                : $p->cuentas()->where('tipo_id', (int)$tipoId)->first();
            if ($cuenta && $cuenta->plan_cuentas_id) {
                return (int) $cuenta->plan_cuentas_id;
            }
        }
        return null;
    }

    private function normalizeLinea(array &$l): void
    {
        $cant   = (float)($l['cantidad'] ?? 0);
        $precio = (float)($l['precio_unitario'] ?? 0);
        $desc   = (float)($l['descuento_pct'] ?? 0);
        $iva    = (float)($l['impuesto_pct'] ?? 0);

        $l['cantidad']        = max(1.0,  round(is_finite($cant)   ? $cant   : 1, 3));
        $l['precio_unitario'] = max(0.0,  round(is_finite($precio) ? $precio : 0, 2));
        $l['descuento_pct']   = min(100.0, max(0.0, round(is_finite($desc) ? $desc : 0, 3)));
        $l['impuesto_pct']    = min(100.0, max(0.0, round(is_finite($iva)  ? $iva  : 0, 3)));
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
            $this->vencimiento = Carbon::parse($this->fecha)->addDays($d)->toDateString();
        }
    }

    public function updatedSocioNegocioId($val): void
    {
        if ($this->bloqueada) return;

        $id = (int) $val;
        $this->factura_id = null;
        $this->numeroFacturaSeleccionada = null;

        $this->setCuentaDesdeCliente($id);
        $this->setPagoDesdeCliente($id);

        $socio = SocioNegocio::with('condicionPago')->find($id);
        $this->condicion_pago_id = $socio?->condicionPago?->id ?: null;

        $this->dispatch('$refresh');
    }

    public function updatedFacturaId($val): void
    {
        if ($this->bloqueada) return;

        $id = (int) $val;
        if ($id > 0) {
            $this->precargarDesdeFactura($id);

            $f = Factura::select('id','prefijo','numero')->find($id);
            $this->numeroFacturaSeleccionada = $f
                ? (trim((string)$f->prefijo) !== '' ? ($f->prefijo.'-'.$f->numero) : (string)$f->numero)
                : (string)$id;
        } else {
            $this->numeroFacturaSeleccionada = null;
        }

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

    private function cargarNota(int $id): void
    {
        try {
            $n = NotaCredito::with(['detalles'])->findOrFail($id);
            $this->nota = $n;

            $this->fill($n->only([
                'serie_id','socio_negocio_id','factura_id','fecha','vencimiento',
                'tipo_pago','plazo_dias','terminos_pago','notas','moneda','estado',
                'cuenta_cobro_id','condicion_pago_id',
            ]));

            $this->lineas = $n->detalles->map(function ($d) {
                $l = [
                    'id'                => $d->id,
                    'producto_id'       => $d->producto_id,
                    'cuenta_ingreso_id' => $d->cuenta_ingreso_id ?: null,
                    'bodega_id'         => $d->bodega_id,
                    'descripcion'       => $d->descripcion,
                    'cantidad'          => (float)$d->cantidad,
                    'precio_unitario'   => (float)$d->precio_unitario,
                    'descuento_pct'     => (float)$d->descuento_pct,
                    'impuesto_id'       => $d->impuesto_id ?? null,
                    'impuesto_pct'      => (float)$d->impuesto_pct,
                ];
                $this->normalizeLinea($l);
                return $l;
            })->toArray();

            $this->resetErrorBag();
            $this->resetValidation();
        } catch (Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo cargar la nota crédito.')->duration(7000);
        }
    }

    private function precargarDesdeFactura(int $facturaId): void
    {
        try {
            $f = Factura::with('detalles')->findOrFail($facturaId);
            $this->socio_negocio_id = $f->socio_negocio_id;
            $this->moneda           = $f->moneda;
            $this->tipo_pago        = 'credito';
            $this->terminos_pago    = 'NC por factura '.$f->prefijo.'-'.$f->numero;

            $this->lineas = $f->detalles->map(function ($d) {
                $l = [
                    'producto_id'       => $d->producto_id,
                    'cuenta_ingreso_id' => $d->cuenta_ingreso_id ?: null,
                    'bodega_id'         => $d->bodega_id,
                    'descripcion'       => 'NC: '.$d->descripcion,
                    'cantidad'          => (float)$d->cantidad,
                    'precio_unitario'   => (float)$d->precio_unitario,
                    'descuento_pct'     => (float)$d->descuento_pct,
                    'impuesto_id'       => $d->impuesto_id ?? null,
                    'impuesto_pct'      => (float)$d->impuesto_pct,
                ];
                $this->normalizeLinea($l);
                return $l;
            })->toArray();

            $this->setCuentaDesdeCliente($f->socio_negocio_id);
        } catch (Throwable $e) {
            report($e);
            PendingToast::create()->warning()->message('No se pudo precargar desde la factura.')->duration(7000);
        }
    }

    public function addLinea(): void
    {
        if ($this->bloqueada) return;

        $l = [
            'producto_id'       => null,
            'cuenta_ingreso_id' => null,
            'bodega_id'         => null,
            'descripcion'       => null,
            'cantidad'          => 1,
            'precio_unitario'   => 0,
            'descuento_pct'     => 0,
            'impuesto_id'       => null,
            'impuesto_pct'      => 0,
        ];
        $this->normalizeLinea($l);
        $this->lineas[] = $l;
        $this->dispatch('$refresh');
    }

    public function removeLinea(int $i): void
    {
        if ($this->bloqueada) return;
        if (!isset($this->lineas[$i])) return;
        array_splice($this->lineas, $i, 1);
        $this->dispatch('$refresh');
    }

    public function setProducto(int $i, $id): void
    {
        if ($this->bloqueada) return;

        try {
            if (!isset($this->lineas[$i])) return;

            $prodId = $id ? (int) $id : null;
            $this->lineas[$i]['producto_id'] = $prodId;

            if (!$prodId) {
                $this->lineas[$i]['cuenta_ingreso_id'] = null;
                $this->lineas[$i]['precio_unitario']   = 0.0;
                $this->lineas[$i]['impuesto_id']       = null;
                $this->lineas[$i]['impuesto_pct']      = 0.0;
                $this->normalizeLinea($this->lineas[$i]);
                $this->dispatch('$refresh');
                return;
            }

            $p = Producto::with([
                'impuesto:id,nombre,porcentaje,monto_fijo,incluido_en_precio,aplica_sobre,activo,vigente_desde,vigente_hasta',
                'cuentaIngreso:id',
                'cuentas:id,producto_id,plan_cuentas_id,tipo_id',
            ])->find($prodId);

            if (!$p) {
                $this->lineas[$i]['cuenta_ingreso_id'] = null;
                $this->lineas[$i]['precio_unitario']   = 0.0;
                $this->lineas[$i]['impuesto_id']       = null;
                $this->lineas[$i]['impuesto_pct']      = 0.0;
                $this->normalizeLinea($this->lineas[$i]);
                $this->dispatch('$refresh');
                return;
            }

            $this->lineas[$i]['cuenta_ingreso_id'] = $this->resolveCuentaIngresoParaProducto($p);

            $precioBase = (float) ($p->precio ?? $p->precio_venta ?? 0.0);
            $ivaPct     = 0.0;
            $impId      = null;

            $imp = $p->impuesto;
            if ($imp && (int)($imp->activo ?? 0) === 1) {
                $aplica = strtoupper((string)($imp->aplica_sobre ?? ''));
                $aplicaVentas = in_array($aplica, ['VENTAS', 'VENTA', 'AMBOS', 'TODOS'], true);

                $hoy   = now()->startOfDay();
                $desde = $imp->vigente_desde ? Carbon::parse($imp->vigente_desde) : null;
                $hasta = $imp->vigente_hasta ? Carbon::parse($imp->vigente_hasta) : null;
                $vigente = (!$desde || $hoy->gte($desde)) && (!$hasta || $hoy->lte($hasta));

                if ($aplicaVentas && $vigente) {
                    $impId = (int)$imp->id;
                    if (!is_null($imp->porcentaje)) {
                        $ivaPct = (float) $imp->porcentaje;
                        if (!empty($imp->incluido_en_precio) && $ivaPct > 0) {
                            $precioBase = $precioBase > 0 ? round($precioBase / (1 + $ivaPct / 100), 2) : 0.0;
                        }
                    } else {
                        $ivaPct = 0.0;
                    }
                }
            }

            if (empty($this->lineas[$i]['descripcion'])) {
                $this->lineas[$i]['descripcion'] = 'NC: ' . (string) $p->nombre;
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
            if ($imp->incluido_en_precio && $imp->porcentaje > 0) {
                $pu = (float)$this->lineas[$i]['precio_unitario'];
                $this->lineas[$i]['precio_unitario'] = $pu > 0 ? round($pu / (1 + $imp->porcentaje/100), 2) : 0.0;
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
            $this->terminos_pago = 'Crédito a '.$d.' días';
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

        $socio = SocioNegocio::with('cuentas')->find($clienteId);
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
            ->where(function ($q) { $q->where('titulo', 0)->orWhereNull('titulo'); })
            ->value('id');
    }

    public function getSubtotalProperty(): float
    {
        $s = 0.0;
        foreach ($this->lineas as $l) {
            $cant   = max(1, (float)($l['cantidad'] ?? 1));
            $precio = max(0, (float)($l['precio_unitario'] ?? 0));
            $desc   = min(100, max(0, (float)($l['descuento_pct'] ?? 0)));
            $base   = $cant * $precio * (1 - $desc / 100);
            $s     += $base;
        }
        return round($s, 2);
    }

    public function getImpuestosTotalProperty(): float
    {
        $i = 0.0;
        foreach ($this->lineas as $l) {
            $cant   = max(1, (float)($l['cantidad'] ?? 1));
            $precio = max(0, (float)($l['precio_unitario'] ?? 0));
            $desc   = min(100, max(0, (float)($l['descuento_pct'] ?? 0)));
            $iva    = min(100, max(0, (float)($l['impuesto_pct'] ?? 0)));
            $base   = $cant * $precio * (1 - $desc / 100);
            $i     += $base * $iva / 100;
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
            $this->terminos_pago = 'Crédito a '.$d.' días';
        }
    }

    protected function persistirBorrador(): void
    {
        if ($this->bloqueada) {
            throw new \RuntimeException('La nota está bloqueada y no se puede modificar.');
        }

        DB::transaction(function () {
            $this->normalizarPagoAntesDeValidar();

            if (!$this->nota) $this->nota = new NotaCredito();

            $serieId = $this->nota->serie_id ?? ($this->serieDefault?->id ?? $this->serie_id);

            $dataCab = [
                'serie_id'          => $serieId,
                'socio_negocio_id'  => $this->socio_negocio_id,
                'factura_id'        => $this->factura_id,
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

            \Illuminate\Database\Eloquent\Model::unguarded(function () use ($dataCab) {
                $this->nota->forceFill($dataCab)->save();
            });

            $this->nota->detalles()->delete();

            $detallesPayload = [];
            foreach ($this->lineas as $l) {
                $detallesPayload[] = [
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
            }

            if (!empty($detallesPayload)) {
                \Illuminate\Database\Eloquent\Model::unguarded(function () use ($detallesPayload) {
                    $this->nota->detalles()->createMany($detallesPayload);
                });
            }

            $this->nota->load('detalles');
            $this->nota->recalcularTotales()->save();

            $this->estado = $this->nota->estado;

            Log::info('Nota crédito guardada (borrador)', [
                'nota_id'   => $this->nota->id,
                'detalles'  => $this->nota->detalles->count(),
            ]);
        }, 3);
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

    private function sanearLineasAntesDeValidar(): void
    {
        foreach ($this->lineas as $i => $l) {
            $this->lineas[$i]['cuenta_ingreso_id'] =
                isset($l['cuenta_ingreso_id']) && $l['cuenta_ingreso_id'] !== ''
                    ? (int) $l['cuenta_ingreso_id']
                    : null;

            $this->lineas[$i]['bodega_id'] =
                isset($l['bodega_id']) && $l['bodega_id'] !== ''
                    ? (int) $l['bodega_id']
                    : null;

            $this->lineas[$i]['producto_id'] =
                isset($l['producto_id']) && $l['producto_id'] !== ''
                    ? (int) $l['producto_id']
                    : null;
        }
    }

    public function guardar(): void
    {
        if ($this->abortIfLocked('guardar')) return;

        try {
            $this->normalizarPagoAntesDeValidar();
            $this->sanearLineasAntesDeValidar();

            if (!$this->validarConToast()) return;

            $this->persistirBorrador();
            PendingToast::create()->success()->message('Nota crédito guardada (ID: '.$this->nota->id.').')->duration(5000);
            $this->dispatch('refrescar-lista-notas');
        } catch (\Throwable $e) {
            Log::error('NC GUARDAR ERROR', ['msg' => $e->getMessage()]);
            $msg = config('app.debug') ? $e->getMessage() : 'No se pudo guardar.';
            PendingToast::create()->error()->message($msg)->duration(9000);
        }
    }

    public function emitir(): void
    {
        if ($this->abortIfLocked('emitir')) return;

        try {
            $this->normalizarPagoAntesDeValidar();
            $this->sanearLineasAntesDeValidar();

            if (!$this->validarConToast()) return;

            DB::transaction(function () {
                $this->persistirBorrador();
                $this->nota->refresh()
                    ->loadMissing(['detalles','cliente'])
                    ->recalcularTotales()
                    ->save();

                if (!$this->serieDefault) {
                    throw new \RuntimeException('No hay serie default activa para Nota Crédito.');
                }

                foreach ($this->nota->detalles as $idx => $d) {
                    if (!$d->producto_id || !$d->bodega_id) {
                        throw new \RuntimeException("La fila #" . ($idx + 1) . " debe tener producto y bodega.");
                    }
                }

                $numero = $this->serieDefault->tomarConsecutivo();
                $this->nota->update([
                    'serie_id' => $this->serieDefault->id,
                    'numero'   => $numero,
                    'prefijo'  => $this->serieDefault->prefijo,
                    'estado'   => 'emitida',
                ]);

                ContabilidadNotaCreditoService::asientoDesdeNotaCredito($this->nota);
                InventarioService::reponerPorNotaCredito($this->nota);

                $this->estado = $this->nota->estado;
            }, 3);

            PendingToast::create()
                ->success()->message('Nota crédito emitida (ID: ' . $this->nota->id . ', No: ' . $this->nota->prefijo . '-' . $this->nota->numero . ').')
                ->duration(6000);
            $this->dispatch('refrescar-lista-notas');

        } catch (\Throwable $e) {
            Log::error('NC EMITIR ERROR', ['msg' => $e->getMessage()]);
            $msg = config('app.debug') ? ($e->getMessage() ?? 'No se pudo emitir la Nota Crédito.') : 'No se pudo emitir la Nota Crédito.';
            PendingToast::create()->error()->message($msg)->duration(12000);
        }
    }

    public function anular(): void
    {
        if ($this->abortIfLocked('anular')) return;

        try {
            if (!$this->nota?->id) return;

            DB::transaction(function () {
                $this->nota->refresh()->loadMissing('detalles');

                InventarioService::revertirReposicionPorNotaCredito($this->nota);
                ContabilidadNotaCreditoService::revertirAsientoNotaCredito($this->nota);

                $this->nota->update(['estado' => 'anulada']);
                $this->estado = 'anulada';
            }, 3);

            PendingToast::create()->info()->message('Nota crédito anulada.')->duration(4500);
            $this->dispatch('refrescar-lista-notas');

        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo anular.')->duration(7000);
        }
    }

    /* ===== Accessors para la vista ===== */

    public function getProximoPreviewProperty(): ?string
    {
        $serieId = (int)($this->serie_id ?? 0);
        if ($serieId <= 0) return null;

        $s = Serie::find($serieId);
        if (!$s) return null;

        $relleno = (int)($s->relleno ?? 0);
        $proximo = (int)($s->proximo ?? 0);
        $hasta   = (int)($s->hasta ?? 0);
        $prefijo = (string)($s->prefijo ?? '');

        if ($hasta > 0 && $proximo > $hasta) {
            return 'Serie agotada';
        }

        $num = $relleno > 0
            ? str_pad((string)$proximo, $relleno, '0', STR_PAD_LEFT)
            : (string)$proximo;

        return trim($prefijo) !== '' ? "{$prefijo}-{$num}" : $num;
    }

    public function getNumeroFacturaSeleccionadaProperty(): ?string
    {
        $fid = (int)($this->factura_id ?? 0);
        if ($fid <= 0) return null;

        $f = Factura::select('prefijo','numero')->find($fid);
        if (!$f) return null;

        $pref = (string)($f->prefijo ?? '');
        $num  = (string)($f->numero ?? '');
        return trim($pref) !== '' ? "{$pref}-{$num}" : $num;
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

    private function condicionPagoDe(SocioNegocio $s): ?array
    {
        if (is_array($s->condiciones_pago_efectivas ?? null)) return $s->condiciones_pago_efectivas;
        if (is_array($s->condiciones_pago ?? null))         return $s->condiciones_pago;
        if (method_exists($s, 'condicionPago')
            && ($s->relationLoaded('condicionPago') ? $s->condicionPago : $s->loadMissing('condicionPago')->condicionPago)) {
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

        $tipo = 'credito'; $plazo = null;
        if ($cp) {
            $raw = strtolower((string)($cp['tipo'] ?? $cp['tipo_credito'] ?? 'credito'));
            $tipo = (str_starts_with($raw, 'cred')) ? 'credito' : 'contado';
            $plazo = $tipo === 'credito' ? (int)($cp['plazo_dias'] ?? 30) : null;
        }

        $this->tipo_pago          = $tipo;
        $this->plazo_dias         = $plazo;
        $this->terminos_pago      = $tipo === 'credito'
            ? 'Crédito a ' . (int)($this->plazo_dias ?: 30) . ' días'
            : 'Contado';

        $this->condicion_pago_id  = $socio?->condicionPago?->id ?: null;

        $this->aplicarFormaPago($this->tipo_pago);
        $this->dispatch('$refresh');
    }

    public function abrirAplicacion(): void
    {
        $this->dispatch('abrir-aplicacion-nota', id: $this->nota?->id, total: $this->total, factura_id: $this->factura_id);
    }
}
