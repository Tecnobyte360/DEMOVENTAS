<?php

namespace App\Livewire\Pos;

use App\Models\Categorias\Categoria;
use App\Models\Categorias\Subcategoria;
use App\Models\ConfiguracionEmpresas\Empresa;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Factura\Factura;
use App\Models\Productos\Producto;
use App\Models\Serie\Serie;
use App\Models\SocioNegocio\SocioNegocio;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\PendingToast;

class PosFactura extends Component
{
    public string $busquedaProducto = '';
    public ?int $categoriaActiva = null;
    public ?int $itemExpandido = null;
    public string $vista = 'productos'; // 'productos' | 'pendientes'

    /** @var array<int, array{producto_id:int,nombre:string,precio:float,cantidad:float,imagen:?string,impuesto_pct:float,cuenta_ingreso_id:?int,descuento_pct:float}> */
    public array $carrito = [];

    public ?int $socioNegocioId = null;
    public string $busquedaCliente = '';
    public ?string $clienteSeleccionadoNombre = null;

    public ?string $observaciones = null;

    public ?int $bodegaDefaultId = null;
    public ?int $serieDefaultId = null;
    public ?int $cuentaCobroDefaultId = null;

    public ?int $facturaEditandoId = null;

    public function mount(): void
    {
        $empresa = Empresa::query()->where('is_activa', true)->first() ?? Empresa::query()->first();
        $this->bodegaDefaultId = $empresa?->bodega_predeterminada_id;
        $this->serieDefaultId = Serie::defaultParaCodigo('factura')?->id;

        $this->categoriaActiva = Categoria::query()->orderBy('nombre')->value('id');

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
        $categorias = Categoria::query()->orderBy('nombre')->get(['id', 'nombre']);

        $productosAgrupados = collect();
        if ($this->categoriaActiva || $this->busquedaProducto !== '') {
            $q = Producto::query()
                ->from('productos as p')
                ->leftJoin('subcategorias as s', 's.id', '=', 'p.subcategoria_id')
                ->select('p.id', 'p.nombre', 'p.precio', 'p.imagen_path', 'p.subcategoria_id', 's.nombre as subcategoria_nombre');

            if ($this->categoriaActiva && $this->busquedaProducto === '') {
                $q->where('s.categoria_id', $this->categoriaActiva);
            }

            if ($this->busquedaProducto !== '') {
                $q->where('p.nombre', 'like', '%' . $this->busquedaProducto . '%');
            }

            $productosAgrupados = $q->orderBy('s.nombre')->orderBy('p.nombre')->limit(200)->get()
                ->groupBy(fn ($p) => $p->subcategoria_nombre ?: 'Sin subcategoría');
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

        $ordenesPendientes = Factura::query()
            ->where('estado', 'borrador')
            ->with('cliente:id,razon_social')
            ->orderByDesc('id')
            ->take(20)
            ->get(['id', 'socio_negocio_id', 'total', 'fecha', 'notas']);

        return view('livewire.pos.pos-factura', [
            'categorias' => $categorias,
            'productosAgrupados' => $productosAgrupados,
            'clientesSugeridos' => $clientesSugeridos,
            'ordenesPendientes' => $ordenesPendientes,
        ]);
    }

    public function setCategoria(int $id): void
    {
        $this->categoriaActiva = $id;
        $this->busquedaProducto = '';
        $this->vista = 'productos';
    }

    public function verPendientes(): void
    {
        $this->vista = 'pendientes';
    }

    public function verProductos(): void
    {
        $this->vista = 'productos';
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
        $this->facturaEditandoId = null;
    }

    public function nuevaOrden(): void
    {
        $this->limpiar();
    }

    public function cargarOrden(int $facturaId): void
    {
        $factura = Factura::with(['detalles', 'cliente'])->find($facturaId);
        if (!$factura) {
            PendingToast::create()->error()->message('Orden no encontrada.')->duration(4000);
            return;
        }
        if ($factura->estado !== 'borrador') {
            PendingToast::create()->warning()->message('Solo se pueden editar órdenes en borrador.')->duration(4000);
            return;
        }

        $this->limpiar();
        $this->facturaEditandoId = $factura->id;
        $this->socioNegocioId = $factura->socio_negocio_id;
        $this->clienteSeleccionadoNombre = $factura->cliente?->razon_social;
        $this->observaciones = $factura->notas;

        foreach ($factura->detalles as $d) {
            $prod = Producto::with('impuesto:id,porcentaje,activo')
                ->select('id', 'nombre', 'precio', 'imagen_path', 'cuenta_ingreso_id', 'impuesto_id', 'es_inventariable')
                ->find($d->producto_id);
            if (!$prod) continue;
            $impPct = ($prod->impuesto && (int) ($prod->impuesto->activo ?? 0) === 1)
                ? (float) ($prod->impuesto->porcentaje ?? 0) : 0.0;

            $this->carrito[$prod->id] = [
                'producto_id' => $prod->id,
                'nombre' => $d->descripcion ?: $prod->nombre,
                'precio' => (float) $d->precio_unitario,
                'cantidad' => (float) $d->cantidad,
                'imagen' => $prod->imagen_path,
                'impuesto_pct' => (float) $d->impuesto_pct ?: $impPct,
                'cuenta_ingreso_id' => $d->cuenta_ingreso_id ?? $prod->cuenta_ingreso_id,
                'descuento_pct' => (float) $d->descuento_pct,
                'es_inventariable' => (bool) ($prod->es_inventariable ?? true),
            ];
        }

        PendingToast::create()->info()->message('Orden #' . $factura->id . ' cargada para editar.')->duration(3500);
    }

    #[On('pago-registrado')]
    public function onPagoRegistrado(): void
    {
        $this->limpiar();
        PendingToast::create()->success()->message('Pago registrado. Carrito limpio.')->duration(4000);
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
        return $this->guardarFactura('cobrar');
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
            $facturaId = DB::transaction(function () {
                if ($this->facturaEditandoId) {
                    $factura = Factura::find($this->facturaEditandoId);
                    if (!$factura || $factura->estado !== 'borrador') {
                        throw new \RuntimeException('La orden ya no se puede editar.');
                    }
                    $factura->update([
                        'socio_negocio_id' => $this->socioNegocioId,
                        'notas' => $this->observaciones,
                        'cuenta_cobro_id' => $factura->cuenta_cobro_id ?: $this->cuentaCobroDefaultId,
                    ]);
                    $factura->detalles()->delete();
                } else {
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
                }

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
                return $factura->id;
            });

            if ($modo === 'borrador') {
                PendingToast::create()->success()
                    ->message('Orden guardada como pendiente (#' . $facturaId . ').')
                    ->duration(4000);
                $this->limpiar();
                return null;
            }

            // modo 'cobrar': abre el modal de pagos existente con la factura ya guardada
            $this->dispatch('abrir-modal-pago', facturaId: $facturaId)
                 ->to(\App\Livewire\Facturas\PagosFactura::class);

            return null;
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()
                ->message(config('app.debug') ? $e->getMessage() : 'No se pudo guardar la orden.')
                ->duration(8000);
            return null;
        }
    }

}
