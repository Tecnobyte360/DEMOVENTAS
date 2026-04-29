<?php

namespace App\Livewire\Pos;

use App\Models\Categorias\Categoria;
use App\Models\Categorias\Subcategoria;
use App\Models\ConfiguracionEmpresas\Empresa;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Factura\Factura;
use App\Models\Productos\Producto;
use App\Models\Serie\Serie;
use App\Models\MediosPago\MedioPagos;
use App\Models\SocioNegocio\SocioNegocio;
use App\Services\ContabilidadService;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Masmerise\Toaster\PendingToast;

class PosFactura extends Component
{
    public string $busquedaProducto = '';
    public ?int $subcategoriaActiva = null;
    public ?int $itemExpandido = null;

    /** @var array<int, array{producto_id:int,nombre:string,precio:float,cantidad:float,imagen:?string,impuesto_pct:float,cuenta_ingreso_id:?int,descuento_pct:float}> */
    public array $carrito = [];

    public ?int $socioNegocioId = null;
    public string $busquedaCliente = '';
    public ?string $clienteSeleccionadoNombre = null;

    public ?string $observaciones = null;

    public ?int $bodegaDefaultId = null;
    public ?int $serieDefaultId = null;
    public ?int $cuentaCobroDefaultId = null;

    public function mount(): void
    {
        $empresa = Empresa::query()->where('is_activa', true)->first() ?? Empresa::query()->first();
        $this->bodegaDefaultId = $empresa?->bodega_predeterminada_id;
        $this->serieDefaultId = Serie::defaultParaCodigo('factura')?->id;

        $this->subcategoriaActiva = Subcategoria::query()->orderBy('nombre')->value('id');

        $this->cuentaCobroDefaultId = PlanCuentas::query()
            ->where('cuenta_activa', 1)
            ->where('titulo', 0)
            ->whereIn('clase_cuenta', ['CAJA_GENERAL', 'CAJA', 'BANCOS'])
            ->orderBy('codigo')
            ->value('id')
            ?? PlanCuentas::query()
                ->where('cuenta_activa', 1)
                ->where('titulo', 0)
                ->where('clase_cuenta', 'CXC_CLIENTES')
                ->orderBy('codigo')
                ->value('id');
    }

    public function render()
    {
        $subcategorias = Subcategoria::query()->orderBy('nombre')->get(['id', 'nombre']);

        $productos = collect();
        if ($this->subcategoriaActiva || $this->busquedaProducto !== '') {
            $q = Producto::query()
                ->select('id', 'nombre', 'precio', 'imagen_path', 'subcategoria_id');

            if ($this->subcategoriaActiva && $this->busquedaProducto === '') {
                $q->where('subcategoria_id', $this->subcategoriaActiva);
            }

            if ($this->busquedaProducto !== '') {
                $q->where('nombre', 'like', '%' . $this->busquedaProducto . '%');
            }

            $productos = $q->orderBy('nombre')->limit(60)->get();
        }

        $clientesSugeridos = collect();
        if (strlen(trim($this->busquedaCliente)) >= 2) {
            $clientesSugeridos = SocioNegocio::clientes()
                ->where(function ($q) {
                    $term = '%' . $this->busquedaCliente . '%';
                    $q->where('razon_social', 'like', $term)
                      ->orWhere('nit', 'like', $term);
                })
                ->orderBy('razon_social')
                ->take(15)
                ->get(['id', 'razon_social', 'nit']);
        }

        return view('livewire.pos.pos-factura', [
            'subcategorias' => $subcategorias,
            'productos' => $productos,
            'clientesSugeridos' => $clientesSugeridos,
        ]);
    }

    public function setSubcategoria(int $id): void
    {
        $this->subcategoriaActiva = $id;
        $this->busquedaProducto = '';
    }

    public function toggleItem(int $productoId): void
    {
        $this->itemExpandido = $this->itemExpandido === $productoId ? null : $productoId;
    }

    public function agregar(int $productoId): void
    {
        if (isset($this->carrito[$productoId])) {
            $this->carrito[$productoId]['cantidad'] += 1;
            return;
        }

        $p = Producto::with(['impuesto:id,porcentaje,activo'])
            ->select('id', 'nombre', 'precio', 'imagen_path', 'cuenta_ingreso_id', 'impuesto_id', 'es_inventariable')
            ->find($productoId);

        if (!$p) return;

        $impPct = ($p->impuesto && (int) ($p->impuesto->activo ?? 0) === 1)
            ? (float) ($p->impuesto->porcentaje ?? 0)
            : 0.0;

        $this->carrito[$productoId] = [
            'producto_id' => $p->id,
            'nombre' => $p->nombre,
            'precio' => (float) ($p->precio ?? 0),
            'cantidad' => 1,
            'imagen' => $p->imagen_path,
            'impuesto_pct' => $impPct,
            'cuenta_ingreso_id' => $p->cuenta_ingreso_id,
            'descuento_pct' => 0,
            'es_inventariable' => (bool) ($p->es_inventariable ?? true),
        ];
    }

    public function setCantidadItem(int $productoId, $cantidad): void
    {
        if (!isset($this->carrito[$productoId])) return;
        $cantidad = max(0, (float) $cantidad);
        if ($cantidad === 0.0) {
            unset($this->carrito[$productoId]);
            if ($this->itemExpandido === $productoId) $this->itemExpandido = null;
        } else {
            $this->carrito[$productoId]['cantidad'] = $cantidad;
        }
    }

    public function setDescuentoItem(int $productoId, $pct): void
    {
        if (!isset($this->carrito[$productoId])) return;
        $this->carrito[$productoId]['descuento_pct'] = max(0, min(100, (float) $pct));
    }

    public function quitar(int $productoId): void
    {
        unset($this->carrito[$productoId]);
        if ($this->itemExpandido === $productoId) $this->itemExpandido = null;
    }

    public function limpiar(): void
    {
        $this->carrito = [];
        $this->observaciones = null;
        $this->socioNegocioId = null;
        $this->clienteSeleccionadoNombre = null;
        $this->busquedaCliente = '';
        $this->itemExpandido = null;
    }

    public function seleccionarCliente(int $id): void
    {
        $c = SocioNegocio::find($id);
        if (!$c) return;
        $this->socioNegocioId = $c->id;
        $this->clienteSeleccionadoNombre = $c->razon_social;
        $this->busquedaCliente = '';
    }

    public function quitarCliente(): void
    {
        $this->socioNegocioId = null;
        $this->clienteSeleccionadoNombre = null;
    }

    public function getSubtotalProperty(): float
    {
        $s = 0.0;
        foreach ($this->carrito as $i) {
            $base = (float) $i['precio'] * (float) $i['cantidad'];
            $s += $base * (1 - ((float) ($i['descuento_pct'] ?? 0) / 100));
        }
        return round($s, 2);
    }

    public function getImpuestosTotalProperty(): float
    {
        $imp = 0.0;
        foreach ($this->carrito as $i) {
            $base = (float) $i['precio'] * (float) $i['cantidad'] * (1 - ((float) ($i['descuento_pct'] ?? 0) / 100));
            $imp += $base * ((float) $i['impuesto_pct'] / 100);
        }
        return round($imp, 2);
    }

    public function getTotalProperty(): float
    {
        return round($this->subtotal + $this->impuestosTotal, 2);
    }

    public function getCantidadTotalProperty(): float
    {
        $c = 0.0;
        foreach ($this->carrito as $i) $c += (float) $i['cantidad'];
        return $c;
    }

    public function holdOrder()
    {
        return $this->guardarFactura('borrador');
    }

    public function proceder()
    {
        return $this->guardarFactura('emitir');
    }

    public function guardar()
    {
        return $this->proceder();
    }

    private function guardarFactura(string $modo)
    {
        if (empty($this->carrito)) {
            PendingToast::create()->warning()->message('El carrito está vacío.')->duration(4000);
            return null;
        }
        if (!$this->socioNegocioId) {
            PendingToast::create()->warning()->message('Selecciona un cliente.')->duration(4000);
            return null;
        }
        if (!$this->serieDefaultId) {
            PendingToast::create()->error()->message('No hay serie por defecto para factura.')->duration(6000);
            return null;
        }

        try {
            $info = DB::transaction(function () use ($modo) {
                $factura = Factura::create([
                    'serie_id' => $this->serieDefaultId,
                    'socio_negocio_id' => $this->socioNegocioId,
                    'fecha' => now()->toDateString(),
                    'vencimiento' => now()->toDateString(),
                    'tipo_pago' => 'contado',
                    'plazo_dias' => null,
                    'terminos_pago' => 'Contado',
                    'notas' => $this->observaciones,
                    'moneda' => 'COP',
                    'estado' => 'borrador',
                    'cuenta_cobro_id' => $this->cuentaCobroDefaultId,
                ]);

                foreach ($this->carrito as $i) {
                    $esInv = (bool) ($i['es_inventariable'] ?? true);
                    $factura->detalles()->create([
                        'producto_id' => $i['producto_id'],
                        'bodega_id' => $esInv ? $this->bodegaDefaultId : null,
                        'cuenta_ingreso_id' => $i['cuenta_ingreso_id'],
                        'descripcion' => $i['nombre'],
                        'cantidad' => $i['cantidad'],
                        'precio_unitario' => $i['precio'],
                        'descuento_pct' => $i['descuento_pct'] ?? 0,
                        'impuesto_pct' => $i['impuesto_pct'],
                    ]);
                }

                $factura->refresh()->recalcularTotales()->save();

                if ($modo === 'borrador') {
                    return ['id' => $factura->id, 'numero' => null];
                }

                return $this->emitirFactura($factura);
            });

            if ($modo === 'borrador') {
                PendingToast::create()->success()->message('Orden retenida (borrador #' . $info['id'] . ').')->duration(4000);
            } else {
                $msg = 'Factura emitida' . ($info['numero'] ? ' #' . $info['numero'] : '') . '.';
                PendingToast::create()->success()->message($msg)->duration(5000);
            }

            $this->limpiar();
            return null;
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()
                ->message(config('app.debug') ? $e->getMessage() : 'No se pudo emitir la factura.')
                ->duration(8000);
            return null;
        }
    }

    private function emitirFactura(Factura $factura): array
    {
        $factura = Factura::with(['detalles', 'pagos', 'serie'])->findOrFail($factura->id);

        foreach ($factura->detalles as $idx => $d) {
            if (empty($d->cuenta_ingreso_id)) {
                throw new \RuntimeException('La fila #' . ($idx + 1) . ' no tiene cuenta de ingreso. Configura la cuenta de ingreso del producto.');
            }
            if (!$d->producto_id) {
                throw new \RuntimeException('La fila #' . ($idx + 1) . ' debe tener producto.');
            }
            $prod = Producto::find($d->producto_id);
            $esInv = $prod && ($prod->es_inventariable ?? true);
            if ($esInv && !$d->bodega_id) {
                throw new \RuntimeException('La fila #' . ($idx + 1) . ' (' . ($d->descripcion ?: 'producto') . ') requiere bodega.');
            }
            if ((float) ($d->cantidad ?? 0) <= 0) {
                throw new \RuntimeException('La fila #' . ($idx + 1) . ' debe tener cantidad mayor a cero.');
            }
        }

        InventarioService::verificarDisponibilidadParaFactura($factura);

        $medio = MedioPagos::where('activo', 1)->orderBy('orden')->first();
        if (!$medio) {
            throw new \RuntimeException('No hay medios de pago activos configurados.');
        }

        $factura->recalcularTotales()->save();
        $factura->refresh();

        $total = round((float) ($factura->total ?? 0), 2);
        if ($total > 0) {
            $factura->registrarPago([
                'fecha' => now()->toDateString(),
                'medio_pago_id' => $medio->id,
                'metodo' => $medio->codigo,
                'monto' => $total,
                'referencia' => 'POS',
                'notas' => 'Cobro automático desde POS',
            ]);
        }

        $serie = $factura->serie;
        if (!$serie) {
            throw new \RuntimeException('La factura no tiene serie asignada.');
        }
        $numero = $serie->tomarConsecutivo();
        $uid = Auth::id();

        $factura->refresh();
        $pagado = round((float) $factura->pagos()->sum('monto'), 2);
        $faltante = round($total - $pagado, 2);
        $estadoFinal = $faltante <= 0.01 ? 'pagada' : 'emitida';

        $dataUpdate = [
            'serie_id' => $serie->id,
            'prefijo' => (string) ($serie->prefijo ?? ''),
            'numero' => $numero,
            'estado' => $estadoFinal,
        ];
        if (Schema::hasColumn('facturas', 'emitido_por_id')) $dataUpdate['emitido_por_id'] = $uid;
        if (Schema::hasColumn('facturas', 'emitido_en')) $dataUpdate['emitido_en'] = now();
        if (Schema::hasColumn('facturas', 'actualizado_por_id')) $dataUpdate['actualizado_por_id'] = $uid;
        if (Schema::hasColumn('facturas', 'pagado')) $dataUpdate['pagado'] = $pagado;
        if (Schema::hasColumn('facturas', 'saldo')) $dataUpdate['saldo'] = max($faltante, 0);

        Factura::whereKey($factura->id)->update($dataUpdate);
        $factura = Factura::with(['detalles', 'pagos', 'serie'])->findOrFail($factura->id);

        ContabilidadService::asientoDesdeFactura($factura);
        InventarioService::descontarPorFactura($factura);

        $factura->recalcularTotales()->save();

        $numeroVisible = trim(($factura->prefijo ? $factura->prefijo . '-' : '') . $factura->numero);
        return ['id' => $factura->id, 'numero' => $numeroVisible];
    }
}
