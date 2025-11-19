<?php

namespace App\Livewire\NotasCredito;

use App\Models\Bodega;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Impuestos\Impuesto;
use App\Models\NotaCredito;
use App\Models\Productos\Producto;
use App\Models\Productos\ProductoCuentaTipo;
use App\Models\Serie\Serie;
use App\Models\SocioNegocio\SocioNegocio;
use App\Services\ContabilidadNotaCreditoCompraService;
use App\Services\InventarioService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\PendingToast;

class NotaCreditoCompraForm extends Component
{
    public ?NotaCredito $nota = null;
    public string $documento = 'nota_credito_compra';
    public ?Serie $serieDefault = null;

    public ?int $serie_id = null;
    public ?int $socio_negocio_id = null;
    public ?int $factura_compra_id = null;
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
    public bool $descontar_inventario = true;
    public ?int $cuenta_cobro_id = null;
    public ?int $condicion_pago_id = null;
    public array $facturasProveedor = [];

    /* =========================================================
     *  VALIDACIÓN
     * ========================================================= */
    protected $rules = [
        'serie_id'                  => 'required|integer|exists:series,id',
        'socio_negocio_id'          => 'required|integer|exists:socio_negocios,id',
        'fecha'                     => 'required|date',
        'vencimiento'               => 'nullable|date|after_or_equal:fecha',
        'tipo_pago'                 => 'required|in:contado,credito',
        'plazo_dias'                => 'nullable|integer|min:1|max:365|required_if:tipo_pago,credito',
        'terminos_pago'             => 'nullable|string|max:255',
        'moneda'                    => 'required|string|size:3',
        'cuenta_cobro_id'           => 'required|integer|exists:plan_cuentas,id',
        'condicion_pago_id'         => 'nullable|integer|exists:condicion_pagos,id',
        'factura_compra_id'         => 'nullable|integer',

        'motivo'                    => 'nullable|string|max:120',
        'descontar_inventario'      => 'boolean',

        'lineas'                    => 'required|array|min:1',
        'lineas.*.producto_id'      => 'required|integer|exists:productos,id',
        'lineas.*.bodega_id'        => 'required|integer|exists:bodegas,id',
        'lineas.*.descripcion'      => 'required|string|max:255',
        'lineas.*.cantidad'         => 'required|numeric|min:1',
        'lineas.*.precio_unitario'  => 'required|numeric|min:0',
        'lineas.*.descuento_pct'    => 'required|numeric|min:0|max:100',
        'lineas.*.impuesto_id'      => 'nullable|integer|exists:impuestos,id',
        'lineas.*.impuesto_pct'     => 'required|numeric|min:0|max:100',
    ];

    protected array $validationAttributes = [
        'serie_id'                     => 'serie',
        'socio_negocio_id'             => 'proveedor',
        'factura_compra_id'            => 'factura de compra',
        'fecha'                        => 'fecha',
        'vencimiento'                  => 'vencimiento',
        'tipo_pago'                    => 'tipo de pago',
        'plazo_dias'                   => 'plazo en días',
        'terminos_pago'                => 'términos de pago',
        'moneda'                       => 'moneda',
        'cuenta_cobro_id'              => 'cuenta CxP proveedor',
        'condicion_pago_id'            => 'condición de pago',
        'motivo'                       => 'motivo',
        'descontar_inventario'         => 'descontar inventario',
        'lineas'                       => 'líneas',
        'lineas.*.producto_id'         => 'producto',
        'lineas.*.bodega_id'           => 'bodega',
        'lineas.*.descripcion'         => 'descripción',
        'lineas.*.cantidad'            => 'cantidad',
        'lineas.*.precio_unitario'     => 'precio unitario',
        'lineas.*.descuento_pct'       => 'descuento (%)',
        'lineas.*.impuesto_id'         => 'indicador de impuesto',
        'lineas.*.impuesto_pct'        => 'porcentaje de impuesto',
    ];

    #[On('abrir-nc-compra')]
    public function abrir(int $id): void
    {
        $this->cargarNota($id);
    }

    public function mount(?int $id = null, ?int $factura_compra_id = null): void
    {
        try {
            $this->fecha = now()->toDateString();
            $this->serieDefault = Serie::defaultParaCodigo($this->documento);
            $this->serie_id = $this->serieDefault?->id;

            if ($id) {
                $this->cargarNota($id);
            } else {
                $this->addLinea();
                $this->terminos_pago = 'Nota crédito de compra';

                if ($factura_compra_id) {
                    $this->factura_compra_id = $factura_compra_id;
                    $this->precargarDesdeFacturaCompra($factura_compra_id);
                }
            }

            $this->setCuentaCxPPorDefecto();
            $this->refrescarFacturasProveedor();
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo inicializar la NC de compra.')->duration(7000);
        }
    }

    public function render()
    {
        try {
            $proveedores = SocioNegocio::proveedores()
                ->orderBy('razon_social')
                ->take(200)->get();

            $productos = Producto::with([
                'impuesto:id,nombre,porcentaje,monto_fijo,incluido_en_precio,aplica_sobre,activo,vigente_desde,vigente_hasta',
            ])->where('activo', 1)->orderBy('nombre')->take(300)->get();

            $idsSel = collect($this->lineas)->pluck('producto_id')->filter()->unique()->values();
            if ($idsSel->isNotEmpty()) {
                $extra = Producto::with(['impuesto'])->whereIn('id', $idsSel)->get();
                $productos = $productos->merge($extra)->unique('id')->values();
            }

            $bodegas = Bodega::orderBy('nombre')->get();

            $cuentasCXP = PlanCuentas::where('cuenta_activa', 1)->where('titulo', 0)
                ->where('clase_cuenta', 'CXP_PROVEEDORES')
                ->orderBy('codigo')->get(['id','codigo','nombre']);

            $cuentasCaja = PlanCuentas::where('cuenta_activa', 1)->where('titulo', 0)
                ->whereIn('clase_cuenta', ['CAJA_GENERAL','BANCOS','CAJA'])
                ->orderBy('codigo')->get(['id','codigo','nombre']);

            $impuestosCompras = Impuesto::activos()
                ->whereIn('aplica_sobre', ['COMPRAS','COMPRA','AMBOS','TODOS'])
                ->orderBy('prioridad')->orderBy('nombre')
                ->get(['id','codigo','nombre','porcentaje','monto_fijo','incluido_en_precio']);

            $tipoId = \App\Models\TiposDocumento\TipoDocumento::whereRaw('LOWER(codigo)=?', [strtolower($this->documento)])->value('id');
            $series = Serie::query()
                ->when($tipoId, fn($q) => $q->where('tipo_documento_id', $tipoId))
                ->orderBy('nombre')
                ->get(['id','nombre','prefijo','desde','hasta','proximo','longitud','es_default','activa']);

            $serieActualId = (int)($this->serie_id ?? $this->nota?->serie_id ?? 0);
            if ($serieActualId && !$series->contains('id', $serieActualId)) {
                if ($sel = Serie::find($serieActualId)) $series->prepend($sel);
            }
            $series = $series->unique('id')->sortByDesc('es_default')->values();

            return view('livewire.notas-credito.nota-credito-compra-form', [
                'proveedores'      => $proveedores,
                'productos'        => $productos,
                'bodegas'          => $bodegas,
                'series'           => $series,
                'serieDefault'     => $this->serieDefault,
                'cuentasCXP'       => $cuentasCXP,
                'cuentasCaja'      => $cuentasCaja,
                'impuestosCompras' => $impuestosCompras,
                'bloqueada'        => $this->bloqueada,
                'facturasProveedor'=> collect($this->facturasProveedor),
            ]);
        } catch (\Throwable $e) {
            Log::error('NC Compra render() fallo', ['msg'=>$e->getMessage()]);
            PendingToast::create()->error()->message('No se pudo cargar datos auxiliares.')->duration(6000);

            return view('livewire.notas-credito.nota-credito-compra-form', [
                'proveedores'=>collect(),'productos'=>collect(),'bodegas'=>collect(),
                'series'=>collect(),'serieDefault'=>$this->serieDefault,
                'cuentasCXP'=>collect(),'cuentasCaja'=>collect(),
                'impuestosCompras'=>collect(),'bloqueada'=>$this->bloqueada,
                'facturasProveedor'=>collect(),
            ]);
        }
    }

    /* ====== Bloqueo ====== */
    public function getBloqueadaProperty(): bool
    {
        $estado = $this->nota->estado ?? $this->estado ?? 'borrador';
        return in_array($estado, ['emitida','anulada'], true);
    }

    private function abortIfLocked(string $accion='editar'): bool
    {
        if ($this->bloqueada) {
            PendingToast::create()->warning()->message("La nota está {$this->estado}; no se puede {$accion}.")->duration(7000);
            return true;
        }
        return false;
    }

    /* ====== Helpers líneas ====== */
    private function normalizeLinea(array &$l): void
    {
        $cant   = (float)($l['cantidad'] ?? 0);
        $precio = (float)($l['precio_unitario'] ?? 0);
        $desc   = (float)($l['descuento_pct'] ?? 0);
        $iva    = (float)($l['impuesto_pct'] ?? 0);

        $l['cantidad']        = max(1.0,  round(is_finite($cant)   ? $cant   : 1, 3));
        $l['precio_unitario'] = max(0.0,  round(is_finite($precio) ? $precio : 0, 4)); // ✅ 4 decimales por si manejas más precisión
        $l['descuento_pct']   = min(100.0, max(0.0, round(is_finite($desc) ? $desc : 0, 3)));
        $l['impuesto_pct']    = min(100.0, max(0.0, round(is_finite($iva)  ? $iva  : 0, 3)));
    }

    public function updated($name, $value): void
    {
        if ($this->bloqueada) return;

        if (preg_match('/^lineas\.(\d+)\.producto_id$/', $name, $m)) {
            $i = (int)$m[1];
            $this->setProducto($i, $value);
            $this->refreshStockLinea($i);
            $this->resetErrorBag(); $this->resetValidation();
            $this->dispatch('$refresh');
            return;
        }
        if (preg_match('/^lineas\.(\d+)\.bodega_id$/', $name, $m)) {
            $this->refreshStockLinea((int)$m[1]); return;
        }
        if (preg_match('/^lineas\.(\d+)\.(cantidad|precio_unitario|descuento_pct|impuesto_pct)$/', $name, $m)) {
            $i = (int)$m[1];
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

        $this->factura_compra_id = null;
        $this->setCuentaCxPDesdeProveedor((int)$val);
        $this->refrescarFacturasProveedor();
        $this->dispatch('$refresh');
    }

    private function setProducto(int $i, $id): void
    {
        if ($this->bloqueada || !isset($this->lineas[$i])) return;

        $prodId = $id ? (int)$id : null;
        $this->lineas[$i]['producto_id'] = $prodId;

        if (!$prodId) {
            foreach (['precio_unitario','impuesto_id','impuesto_pct'] as $k) $this->lineas[$i][$k] = 0;
            $this->normalizeLinea($this->lineas[$i]); $this->dispatch('$refresh'); return;
        }

        $p = Producto::with(['impuesto'])->find($prodId);
        if (!$p) {
            foreach (['precio_unitario','impuesto_id','impuesto_pct'] as $k) $this->lineas[$i][$k] = 0;
            $this->normalizeLinea($this->lineas[$i]); $this->dispatch('$refresh'); return;
        }

        $precioBase = (float)($p->costo ?? $p->precio_compra ?? $p->precio ?? 0.0);

        $ivaPct = 0.0; $impId = null;
        $imp = $p->impuesto;
        if ($imp && (int)($imp->activo ?? 0) === 1) {
            $aplica = strtoupper((string)($imp->aplica_sobre ?? ''));
            $aplicaCompras = in_array($aplica, ['COMPRAS','COMPRA','AMBOS','TODOS'], true);

            $hoy = now()->startOfDay();
            $desde = $imp->vigente_desde ? Carbon::parse($imp->vigente_desde) : null;
            $hasta = $imp->vigente_hasta ? Carbon::parse($imp->vigente_hasta) : null;
            $vigente = (!$desde || $hoy->gte($desde)) && (!$hasta || $hoy->lte($hasta));

            if ($aplicaCompras && $vigente) {
                $impId = (int)$imp->id;
                if (!is_null($imp->porcentaje)) {
                    $ivaPct = (float)$imp->porcentaje;
                    if (!empty($imp->incluido_en_precio) && $ivaPct > 0) {
                        $precioBase = $precioBase > 0 ? round($precioBase / (1 + $ivaPct/100), 2) : 0.0;
                    }
                }
            }
        }

        if (empty($this->lineas[$i]['descripcion'])) {
            $this->lineas[$i]['descripcion'] = 'NC Compra: ' . (string)$p->nombre;
        }

        $this->lineas[$i]['precio_unitario'] = $precioBase;
        $this->lineas[$i]['impuesto_id']     = $impId;
        $this->lineas[$i]['impuesto_pct']    = $ivaPct;

        $this->normalizeLinea($this->lineas[$i]);
        $this->dispatch('$refresh');
    }

    public function setImpuesto(int $i, $impuestoId): void
    {
        if ($this->bloqueada || !isset($this->lineas[$i])) return;

        $impId = $impuestoId ? (int)$impuestoId : null;
        $this->lineas[$i]['impuesto_id'] = $impId;

        if (!$impId) {
            $this->lineas[$i]['impuesto_pct'] = 0.0;
        } else {
            $imp = Impuesto::find($impId);
            if ($imp && $imp->activo && !is_null($imp->porcentaje)) {
                if ($imp->incluido_en_precio && $imp->porcentaje > 0) {
                    $pu = (float)$this->lineas[$i]['precio_unitario'];
                    $this->lineas[$i]['precio_unitario'] = $pu > 0 ? round($pu / (1 + $imp->porcentaje/100), 2) : 0.0;
                }
                $this->lineas[$i]['impuesto_pct'] = (float)$imp->porcentaje;
            } else {
                $this->lineas[$i]['impuesto_pct'] = 0.0;
            }
        }

        $this->normalizeLinea($this->lineas[$i]);
        $this->dispatch('$refresh');
    }

    public function addLinea(): void
    {
        if ($this->bloqueada) return;
        $l = [
            'producto_id'      => null,
            'bodega_id'        => null,
            'descripcion'      => null,
            'cantidad'         => 1,
            'precio_unitario'  => 0,
            'descuento_pct'    => 0,
            'impuesto_id'      => null,
            'impuesto_pct'     => 0,
        ];
        $this->normalizeLinea($l);
        $this->lineas[] = $l;
        $this->dispatch('$refresh');
    }

    public function removeLinea(int $i): void
    {
        if ($this->bloqueada || !isset($this->lineas[$i])) return;
        array_splice($this->lineas, $i, 1);
        $this->dispatch('$refresh');
    }

    /* ====== Pago / Vencimiento ====== */
    public function aplicarFormaPago(string $tipo): void
    {
        if ($this->bloqueada) return;
        $this->tipo_pago = $tipo;
        if ($tipo === 'contado') {
            $this->plazo_dias = null;
            $this->vencimiento = $this->fecha;
        } else {
            $d = max((int)($this->plazo_dias ?: 30), 1);
            $this->plazo_dias = $d;
            $this->vencimiento = Carbon::parse($this->fecha)->addDays($d)->toDateString();
        }
    }

    public function updatedFecha(): void 
    { 
        if (!$this->bloqueada) $this->aplicarFormaPago($this->tipo_pago); 
    }

    public function updatedPlazoDias(): void
    {
        if ($this->bloqueada || $this->tipo_pago!=='credito') return;
        $d = max((int)$this->plazo_dias, 1);
        $this->plazo_dias  = $d;
        $this->vencimiento = Carbon::parse($this->fecha)->addDays($d)->toDateString();
        $this->terminos_pago = 'Crédito a '.$d.' días';
    }

    /* ====== Totales ====== */
    public function getSubtotalProperty(): float
    {
        $s=0.0; 
        foreach ($this->lineas as $l) {
            $cant=max(1,(float)($l['cantidad']??1));
            $precio=max(0,(float)($l['precio_unitario']??0));
            $desc=min(100,max(0,(float)($l['descuento_pct']??0)));
            $s += $cant*$precio*(1-$desc/100);
        } 
        return round($s,2);
    }

    public function getImpuestosTotalProperty(): float
    {
        $i=0.0; 
        foreach ($this->lineas as $l) {
            $cant=max(1,(float)($l['cantidad']??1));
            $precio=max(0,(float)($l['precio_unitario']??0));
            $desc=min(100,max(0,(float)($l['descuento_pct']??0)));
            $iva=min(100,max(0,(float)($l['impuesto_pct']??0)));
            $i += ($cant*$precio*(1-$desc/100))*$iva/100;
        } 
        return round($i,2);
    }

    public function getTotalProperty(): float 
    { 
        return round($this->subtotal + $this->impuestosTotal, 2); 
    }

    /* ====== Persistencia ====== */
    private function normalizarPagoAntesDeValidar(): void
    {
        if ($this->tipo_pago==='contado') {
            $this->plazo_dias=null; 
            $this->vencimiento=$this->fecha; 
            $this->terminos_pago='Contado';
        } else {
            $d=max((int)($this->plazo_dias?:30),1);
            $this->plazo_dias=$d; 
            $this->vencimiento=Carbon::parse($this->fecha)->addDays($d)->toDateString();
            $this->terminos_pago='Crédito a '.$d.' días';
        }
    }

    private function sanearLineasAntesDeValidar(): void
    {
        foreach ($this->lineas as $i=>$l) {
            $this->lineas[$i]['bodega_id']   = isset($l['bodega_id']) && $l['bodega_id']!=='' ? (int)$l['bodega_id'] : null;
            $this->lineas[$i]['producto_id'] = isset($l['producto_id']) && $l['producto_id']!=='' ? (int)$l['producto_id'] : null;
        }
    }

    private function validarConToast(): bool
    {
        try { 
            $this->validate($this->rules, [], $this->validationAttributes); 
            return true; 
        }
        catch (ValidationException $e) {
            $first = collect($e->validator->errors()->all())->first() ?: 'Revisa los campos obligatorios.';
            PendingToast::create()->error()->message($first)->duration(9000);
            return false;
        }
    }

    protected function persistirBorrador(): void
    {
        if ($this->bloqueada) throw new \RuntimeException('La nota está bloqueada.');

        DB::transaction(function () {
            $this->normalizarPagoAntesDeValidar();
            $this->sanearLineasAntesDeValidar();

            if (!$this->nota) $this->nota = new NotaCredito();

            $serieId = $this->serie_id ?? ($this->serieDefault?->id ?? $this->nota?->serie_id);

            $dataCab = [
                'serie_id'          => $serieId,
                'socio_negocio_id'  => $this->socio_negocio_id,
                'factura_id'        => $this->factura_compra_id,
                'fecha'             => $this->fecha,
                'vencimiento'       => $this->vencimiento,
                'moneda'            => $this->moneda,
                'tipo_pago'         => $this->tipo_pago,
                'plazo_dias'        => $this->plazo_dias,
                'terminos_pago'     => $this->terminos_pago,
                'notas'             => $this->notas,
                'motivo'            => $this->motivo,
                'estado'            => 'borrador',
                'cuenta_cobro_id'   => $this->cuenta_cobro_id,
                'condicion_pago_id' => $this->condicion_pago_id,
                'es_compra'         => 1,
                'reponer_inventario'=> 0,
            ];

            \Illuminate\Database\Eloquent\Model::unguarded(function () use ($dataCab) {
                $this->nota->forceFill($dataCab)->save();
            });

            $this->nota->detalles()->delete();

            $detallesPayload = [];
            foreach ($this->lineas as $l) {
                $detallesPayload[] = [
                    'producto_id'      => $l['producto_id'] ?? null,
                    'bodega_id'        => isset($l['bodega_id']) ? (int)$l['bodega_id'] : null,
                    'descripcion'      => $l['descripcion'] ?? null,
                    'cantidad'         => (float) ($l['cantidad'] ?? 1),
                    'precio_unitario'  => (float) ($l['precio_unitario'] ?? 0),
                    'descuento_pct'    => (float) ($l['descuento_pct'] ?? 0),
                    'impuesto_id'      => $l['impuesto_id'] ?? null,
                    'impuesto_pct'     => (float) ($l['impuesto_pct'] ?? 0),
                ];
            }

            if (!empty($detallesPayload)) {
                \Illuminate\Database\Eloquent\Model::unguarded(function () use ($detallesPayload) {
                    $this->nota->detalles()->createMany($detallesPayload);
                });
            }

            $this->nota->load('detalles');
            if (method_exists($this->nota, 'recalcularTotales')) $this->nota->recalcularTotales()->save();

            $this->estado = $this->nota->estado;
        }, 3);
    }

    public function guardar(): void
    {
        if ($this->abortIfLocked('guardar')) return;
        try {
            $this->normalizarPagoAntesDeValidar();
            $this->sanearLineasAntesDeValidar();
            if (!$this->validarConToast()) return;

            $this->persistirBorrador();
            PendingToast::create()->success()->message('NC de compra guardada (ID: '.$this->nota->id.').')->duration(5000);
            $this->dispatch('refrescar-lista-nc-compra');
        } catch (\Throwable $e) {
            Log::error('NC COMPRA GUARDAR ERROR', ['msg'=>$e->getMessage()]);
            PendingToast::create()->error()->message(config('app.debug')?$e->getMessage():'No se pudo guardar.')->duration(9000);
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
                $this->nota->refresh()->loadMissing(['detalles', 'cliente']);

                // ✅ Validar que todas las líneas tengan producto y bodega
                foreach ($this->nota->detalles as $idx => $d) {
                    if (!$d->producto_id || !$d->bodega_id) {
                        throw new \RuntimeException("La fila #" . ($idx + 1) . " debe tener producto y bodega.");
                    }
                }

                $serie = null;
                if ($this->serie_id) {
                    $serie = Serie::find((int)$this->serie_id);
                }
                if (!$serie) {
                    $serie = $this->serieDefault;
                }
                if (!$serie) {
                    throw new \RuntimeException('No hay serie activa para Nota Crédito de Compra.');
                }

                if ((int)($serie->activa ?? 0) !== 1) {
                    throw new \RuntimeException('La serie seleccionada no está activa.');
                }

                $len     = (int)($serie->longitud ?? 6);
                $proximo = (int)($serie->proximo ?? 0);
                $hasta   = (int)($serie->hasta ?? 0);
                if ($hasta > 0 && $proximo > $hasta) {
                    throw new \RuntimeException('La serie seleccionada está agotada.');
                }

                $numero = $serie->tomarConsecutivo();

                $this->nota->update([
                    'serie_id' => $serie->id,
                    'numero'   => $numero,
                    'prefijo'  => (string)($serie->prefijo ?? ''),
                    'estado'   => 'emitida',
                ]);

                if ($this->descontar_inventario && method_exists(InventarioService::class, 'salidaPorNotaCreditoCompra')) {
                    InventarioService::salidaPorNotaCreditoCompra($this->nota);
                }

                ContabilidadNotaCreditoCompraService::asientoDesdeNotaCreditoCompra($this->nota);

                $this->estado = $this->nota->estado;
            }, 3);

            PendingToast::create()
                ->success()
                ->message('Nota Crédito de compra emitida correctamente.')
                ->duration(6000);

            $this->dispatch('refrescar-lista-nc-compra');
        } catch (\Throwable $e) {
            Log::error('NC COMPRA EMITIR ERROR', ['msg' => $e->getMessage()]);
            PendingToast::create()
                ->error()
                ->message(config('app.debug') ? $e->getMessage() : 'No se pudo emitir.')
                ->duration(9000);
        }
    }

    public function anular(): void
    {
        if ($this->estado !== 'emitida') {
            PendingToast::create()->warning()->message('Solo puedes anular notas emitidas.')->duration(5000);
            return;
        }

        try {
            if (!$this->nota?->id) return;

            DB::transaction(function () {
                $this->nota->refresh()->loadMissing('detalles');

                if ($this->descontar_inventario && method_exists(InventarioService::class, 'revertirSalidaPorNotaCreditoCompra')) {
                    InventarioService::revertirSalidaPorNotaCreditoCompra($this->nota);
                }

                // ✅ Usar el servicio contable de COMPRAS, no el genérico de ventas
                ContabilidadNotaCreditoCompraService::revertirAsientoNotaCreditoCompra($this->nota);

                $this->nota->update(['estado' => 'anulada']);
                $this->estado = 'anulada';
            }, 3);

            PendingToast::create()->info()->message('NC de compra anulada.')->duration(4500);
            $this->dispatch('refrescar-lista-nc-compra');
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo anular.')->duration(7000);
        }
    }

    /* ====== Vistas auxiliares ====== */
    public function getProximoPreviewProperty(): ?string
    {
        $serieId = (int)($this->serie_id ?? 0);
        if ($serieId <= 0) return null;
        $s = Serie::find($serieId); 
        if (!$s) return null;

        $len=(int)($s->longitud ?? 6); 
        $proximo=(int)($s->proximo ?? 0);
        $hasta=(int)($s->hasta ?? 0); 
        $pref=(string)($s->prefijo ?? '');
        if ($hasta>0 && $proximo>$hasta) return 'Serie agotada';
        $num = $len>0 ? str_pad((string)$proximo,$len,'0',STR_PAD_LEFT) : (string)$proximo;
        return trim($pref) !== '' ? "{$pref}-{$num}" : $num;
    }

    public function getStockDeLinea(int $i): float
    {
        return (float)($this->stockVista[$i] ?? 0.0);
    }

    private function refreshStockLinea(int $i): void
    {
        if (!isset($this->lineas[$i])) return;
        $pid = (int)($this->lineas[$i]['producto_id'] ?? 0);
        $bid = (int)($this->lineas[$i]['bodega_id'] ?? 0);

        if ($pid<=0 || $bid<=0) { 
            $this->stockVista[$i]=0.0; 
            $this->dispatch('$refresh'); 
            return; 
        }

        $stock = \App\Models\Productos\ProductoBodega::query()
            ->where('producto_id',$pid)
            ->where('bodega_id',$bid)
            ->value('stock');

        $this->stockVista[$i] = (float)($stock ?? 0);
        $this->dispatch('$refresh');
    }

    /* ====== Facturas proveedor ====== */
    private function refrescarFacturasProveedor(): void
    {
        $this->facturasProveedor = [];
        $provId = (int)($this->socio_negocio_id ?? 0);
        if ($provId <= 0) return;

        try {
            $tipoId = \App\Models\TiposDocumento\TipoDocumento::whereRaw('LOWER(codigo)=?', ['factura_compra'])
                ->value('id');

            $q = \App\Models\Factura\Factura::query()
                ->where('socio_negocio_id', $provId)
                ->when($tipoId, fn ($qq) =>
                    $qq->whereHas('serie', fn ($s) => $s->where('tipo_documento_id', $tipoId))
                )
                ->orderByDesc('fecha')->orderByDesc('id')
                ->limit(200)
                ->select(['id','prefijo','numero','fecha','total','saldo']);

            $rows = $q->get();

            $this->facturasProveedor = $rows->map(function ($f) {
                $num   = (string)($f->numero ?? '');
                $pref  = trim((string)($f->prefijo ?? ''));
                $numFmt = $pref !== '' ? "{$pref}-{$num}" : $num;
                $fecha = $f->fecha instanceof \Carbon\Carbon ? $f->fecha->toDateString() : (string)$f->fecha;

                return [
                    'id'    => (int)$f->id,
                    'numero'=> $numFmt,
                    'fecha' => $fecha,
                    'total' => (float)($f->total ?? 0),
                    'saldo' => (float)($f->saldo ?? 0),
                ];
            })->all();
        } catch (\Throwable $e) {
            Log::warning('refrescarFacturasProveedor fallo', ['msg' => $e->getMessage()]);
            $this->facturasProveedor = [];
        }
    }

    public function updatedFacturaCompraId($id): void
    {
        if ($this->bloqueada) return;

        $id = (int) ($id ?: 0);

        if ($id <= 0) {
            $this->lineas = [];
            $this->addLinea();
            $this->dispatch('$refresh');
            return;
        }

        $this->precargarDesdeFacturaCompra($id);
    }

    private function precargarDesdeFacturaCompra(int $id): void
    {
        try {
            $f = \App\Models\Factura\Factura::with([
                'detalles.producto.cuentas.cuentaPUC',
                'detalles.producto.cuentas.tipo',
                'detalles.producto.subcategoria.cuentas',
                'detalles.producto.impuesto',
                'cliente',
                'serie',
            ])->findOrFail($id);

            $this->socio_negocio_id = (int) $f->socio_negocio_id;
            $this->moneda        = $f->moneda ?? $this->moneda;
            $this->tipo_pago     = 'credito';
            $this->terminos_pago = 'NC compra por factura ' . trim(($f->prefijo ?? '').'-'.($f->numero ?? $id), '-');

            $this->lineas = collect($f->detalles)->map(function ($d) {
                $producto = $d->producto;
                $cuentaInventarioId = null;
                
                if (!empty($d->cuenta_inventario_id)) {
                    $cuentaInventarioId = (int) $d->cuenta_inventario_id;
                }
                
                if (!$cuentaInventarioId && $producto) {
                    $cuentaInventarioId = $this->resolveCuentaInventarioParaProducto($producto);
                }

                $l = [
                    'producto_id'     => (int) ($d->producto_id ?? 0),
                    'bodega_id'       => (int) ($d->bodega_id ?? 0),
                    'descripcion'     => 'NC Compra: ' . ($d->descripcion ?? ($producto->nombre ?? '')),
                    'cantidad'        => (float) ($d->cantidad ?? 1),
                    'precio_unitario' => (float) ($d->precio_unitario ?? $d->precio ?? $d->costo_unitario ?? 0),
                    'descuento_pct'   => (float) ($d->descuento_pct ?? $d->descuento ?? 0),
                    'impuesto_id'     => $d->impuesto_id ?? null,
                    'impuesto_pct'    => (float) ($d->impuesto_pct ?? $d->iva_pct ?? 0),
                    'cuenta_devolucion_id' => $cuentaInventarioId,
                ];
                
                $this->normalizeLinea($l);
                return $l;
            })->values()->all();

            $this->setCuentaCxPDesdeProveedor($f->socio_negocio_id);
            
            if (!empty($f->cuenta_cobro_id) && PlanCuentas::whereKey($f->cuenta_cobro_id)->exists()) {
                $this->cuenta_cobro_id = (int) $f->cuenta_cobro_id;
            }

            $this->refrescarFacturasProveedor();

            foreach (array_keys($this->lineas) as $i) {
                $this->refreshStockLinea($i);
            }

            $this->resetErrorBag();
            $this->resetValidation();
            $this->dispatch('$refresh');

            PendingToast::create()
                ->success()
                ->message('Productos cargados desde factura de compra ✓')
                ->duration(4000);

        } catch (\Throwable $e) {
            Log::error('precargarDesdeFacturaCompra ERROR', [
                'factura_id' => $id,
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            PendingToast::create()
                ->error()
                ->message('No se pudo cargar la factura: ' . $e->getMessage())
                ->duration(7000);
        }
    }

    /**
     * 🔥 Resolver cuenta de inventario/gasto para producto en NC de compra
     */
    private function resolveCuentaInventarioParaProducto(?Producto $p): ?int
    {
        if (!$p) return null;

        // 1. Si el producto tiene cuenta de inventario directa
        if (!empty($p->cuenta_inventario_id) && PlanCuentas::whereKey($p->cuenta_inventario_id)->exists()) {
            return (int) $p->cuenta_inventario_id;
        }

        // 2. Buscar en cuentas del producto por tipo "INVENTARIO"
        $tipoInvId = cache()->remember('producto_cuenta_tipo_inventario_id', 600, fn () =>
            ProductoCuentaTipo::where('codigo', 'INVENTARIO')->value('id')
        );

        if (!$tipoInvId) {
            // Fallback: buscar tipo "GASTO_COMPRAS"
            $tipoInvId = cache()->remember('producto_cuenta_tipo_gasto_compras_id', 600, fn () =>
                ProductoCuentaTipo::where('codigo', 'GASTO_COMPRAS')->value('id')
            );
        }

        if (!$tipoInvId) return null;

        // Opción A: Movimiento contable según artículo
        if ($p->mov_contable_segun === 'ARTICULO' || $p->mov_contable_segun === Producto::MOV_SEGUN_ARTICULO) {
            $cuenta = $p->relationLoaded('cuentas')
                ? $p->cuentas->firstWhere('tipo_id', (int)$tipoInvId)
                : $p->cuentas()->where('tipo_id', (int)$tipoInvId)->first();

            if ($cuenta && !empty($cuenta->plan_cuentas_id)) {
                return (int) $cuenta->plan_cuentas_id;
            }
        }

        // Opción B: Movimiento contable según subcategoría
        if (($p->mov_contable_segun === 'SUBCATEGORIA' || $p->mov_contable_segun === Producto::MOV_SEGUN_SUBCATEGORIA) 
            && !empty($p->subcategoria_id)) {
            
            if ($p->relationLoaded('subcategoria') && $p->subcategoria?->relationLoaded('cuentas')) {
                $sc = $p->subcategoria->cuentas->firstWhere('tipo_id', (int)$tipoInvId);
                if ($sc && !empty($sc->plan_cuentas_id)) {
                    return (int) $sc->plan_cuentas_id;
                }
            }

            $sc = \App\Models\Categorias\SubcategoriaCuenta::query()
                ->where('subcategoria_id', (int)$p->subcategoria_id)
                ->where('tipo_id', (int)$tipoInvId)
                ->first();

            if ($sc && !empty($sc->plan_cuentas_id)) {
                return (int) $sc->plan_cuentas_id;
            }
        }

        return null;
    }

    /**
     * 🔥 Cargar nota de crédito existente
     */
    private function cargarNota(int $id): void
    {
        try {
            $nc = NotaCredito::with(['detalles', 'serie.tipo'])->findOrFail($id);
            $this->nota = $nc;

            $this->fill($nc->only([
                'serie_id',
                'socio_negocio_id',
                'factura_compra_id',
                'fecha',
                'vencimiento',
                'tipo_pago',
                'plazo_dias',
                'terminos_pago',
                'notas',
                'moneda',
                'estado',
                'motivo',
                'cuenta_cobro_id',
                'condicion_pago_id',
                'descontar_inventario',
            ]));

            // Mapear detalles a líneas
            $this->lineas = $nc->detalles->map(function ($d) {
                $l = [
                    'id'              => $d->id,
                    'producto_id'     => $d->producto_id,
                    'bodega_id'       => $d->bodega_id,
                    'descripcion'     => $d->descripcion,
                    'cantidad'        => (float)$d->cantidad,
                    'precio_unitario' => (float)$d->precio_unitario,
                    'descuento_pct'   => (float)$d->descuento_pct,
                    'impuesto_id'     => $d->impuesto_id ?? null,
                    'impuesto_pct'    => (float)$d->impuesto_pct,
                ];
                $this->normalizeLinea($l);
                return $l;
            })->toArray();

            $this->resetErrorBag();
            $this->resetValidation();
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo cargar la nota de crédito.')->duration(7000);
        }
    }

    /* ====== Cuentas por defecto (CxP Proveedores) ====== */
    private function setCuentaCxPPorDefecto(): void
    {
        if ($this->cuenta_cobro_id && PlanCuentas::whereKey($this->cuenta_cobro_id)->exists()) return;

        if ($this->socio_negocio_id) {
            $this->setCuentaCxPDesdeProveedor((int)$this->socio_negocio_id);
            return;
        }

        $this->cuenta_cobro_id =
            $this->idCuentaPorClase('CXP_PROVEEDORES')
            ?: $this->idCuentaPorClase('BANCOS')
            ?: $this->idCuentaPorClase('CAJA_GENERAL');

        $this->dispatch('$refresh');
    }

    private function setCuentaCxPDesdeProveedor(?int $proveedorId): void
    {
        if ($this->bloqueada || !$proveedorId) return;

        $socio = SocioNegocio::with('cuentas')->find($proveedorId);
        $cxp = $socio?->cuentas?->cuenta_cxp_id ?? null;

        if ($cxp && PlanCuentas::whereKey($cxp)->exists()) {
            $this->cuenta_cobro_id = (int)$cxp;
        } else {
            $this->cuenta_cobro_id =
                $this->idCuentaPorClase('CXP_PROVEEDORES')
                ?: $this->idCuentaPorClase('BANCOS')
                ?: $this->idCuentaPorClase('CAJA_GENERAL');
        }
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
}
