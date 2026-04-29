<?php

namespace App\Livewire\Pos;

use App\Models\Categorias\Categoria;
use App\Models\ConfiguracionEmpresas\Empresa;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Factura\Factura;
use App\Models\Productos\Producto;
use App\Models\Serie\Serie;
use App\Models\SocioNegocio\SocioNegocio;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Masmerise\Toaster\PendingToast;

class PosFactura extends Component
{
    public string $busquedaProducto = '';
    public ?int $categoriaAbierta = null;

    /** @var array<int, array{producto_id:int,nombre:string,precio:float,cantidad:float,imagen:?string,impuesto_pct:float,cuenta_ingreso_id:?int}> */
    public array $carrito = [];

    public ?int $socioNegocioId = null;
    public string $busquedaCliente = '';
    public ?string $clienteSeleccionadoNombre = null;

    public float $descuentoPct = 0;
    public float $descuentoValor = 0;

    public ?string $observaciones = null;

    public ?int $bodegaDefaultId = null;
    public ?int $serieDefaultId = null;
    public ?int $cuentaCobroDefaultId = null;

    public function mount(): void
    {
        $empresa = Empresa::query()->where('is_activa', true)->first() ?? Empresa::query()->first();
        $this->bodegaDefaultId = $empresa?->bodega_predeterminada_id;
        $this->serieDefaultId = Serie::defaultParaCodigo('factura')?->id;

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
        $categorias = Categoria::query()
            ->when(property_exists(Categoria::class, 'activo') || true, fn ($q) => $q)
            ->with(['subcategorias.productos' => function ($q) {
                $q->where('activo', 1);
                if ($this->busquedaProducto !== '') {
                    $q->where('nombre', 'like', '%' . $this->busquedaProducto . '%');
                }
            }])
            ->orderBy('nombre')
            ->get();

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
            'categorias' => $categorias,
            'clientesSugeridos' => $clientesSugeridos,
        ]);
    }

    public function toggleCategoria(int $id): void
    {
        $this->categoriaAbierta = $this->categoriaAbierta === $id ? null : $id;
    }

    public function agregar(int $productoId): void
    {
        if (isset($this->carrito[$productoId])) {
            $this->carrito[$productoId]['cantidad'] += 1;
            return;
        }

        $p = Producto::with(['impuesto:id,porcentaje,activo'])
            ->select('id', 'nombre', 'precio', 'imagen_path', 'cuenta_ingreso_id', 'impuesto_id')
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
        ];

        PendingToast::create()->success()->message('Agregado')->duration(1500);
    }

    public function incrementar(int $productoId): void
    {
        if (isset($this->carrito[$productoId])) {
            $this->carrito[$productoId]['cantidad'] += 1;
        }
    }

    public function decrementar(int $productoId): void
    {
        if (!isset($this->carrito[$productoId])) return;
        $this->carrito[$productoId]['cantidad'] -= 1;
        if ($this->carrito[$productoId]['cantidad'] <= 0) {
            unset($this->carrito[$productoId]);
        }
    }

    public function quitar(int $productoId): void
    {
        unset($this->carrito[$productoId]);
    }

    public function setCantidad(int $productoId, $cantidad): void
    {
        if (!isset($this->carrito[$productoId])) return;
        $cantidad = max(0, (float) $cantidad);
        if ($cantidad === 0.0) {
            unset($this->carrito[$productoId]);
        } else {
            $this->carrito[$productoId]['cantidad'] = $cantidad;
        }
    }

    public function limpiar(): void
    {
        $this->carrito = [];
        $this->descuentoPct = 0;
        $this->descuentoValor = 0;
        $this->observaciones = null;
        $this->socioNegocioId = null;
        $this->clienteSeleccionadoNombre = null;
        $this->busquedaCliente = '';
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
            $s += (float) $i['precio'] * (float) $i['cantidad'];
        }
        return $s;
    }

    public function getDescuentoTotalProperty(): float
    {
        $sub = $this->subtotal;
        $porPct = $sub * ((float) $this->descuentoPct / 100);
        return round($porPct + (float) $this->descuentoValor, 2);
    }

    public function getImpuestosTotalProperty(): float
    {
        $sub = $this->subtotal;
        if ($sub <= 0) return 0.0;
        $factor = max(0, 1 - ($this->descuentoTotal / $sub));
        $imp = 0.0;
        foreach ($this->carrito as $i) {
            $base = (float) $i['precio'] * (float) $i['cantidad'] * $factor;
            $imp += $base * ((float) $i['impuesto_pct'] / 100);
        }
        return round($imp, 2);
    }

    public function getTotalProperty(): float
    {
        return round(max(0, $this->subtotal - $this->descuentoTotal) + $this->impuestosTotal, 2);
    }

    public function getCantidadTotalProperty(): float
    {
        $c = 0.0;
        foreach ($this->carrito as $i) $c += (float) $i['cantidad'];
        return $c;
    }

    public function guardar()
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
                $sub = $this->subtotal;
                $descTotal = $this->descuentoTotal;
                $descPctEfectivo = $sub > 0 ? round(($descTotal / $sub) * 100, 3) : 0;

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
                ]);

                foreach ($this->carrito as $i) {
                    $factura->detalles()->create([
                        'producto_id' => $i['producto_id'],
                        'bodega_id' => $this->bodegaDefaultId,
                        'cuenta_ingreso_id' => $i['cuenta_ingreso_id'],
                        'descripcion' => $i['nombre'],
                        'cantidad' => $i['cantidad'],
                        'precio_unitario' => $i['precio'],
                        'descuento_pct' => $descPctEfectivo,
                        'impuesto_pct' => $i['impuesto_pct'],
                    ]);
                }

                $factura->refresh()->recalcularTotales()->save();
                return $factura->id;
            });

            PendingToast::create()->success()->message('Factura creada como borrador.')->duration(4000);
            return redirect()->route('facturas.edit', ['id' => $facturaId]);
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()
                ->message(config('app.debug') ? $e->getMessage() : 'No se pudo crear la factura.')
                ->duration(8000);
            return null;
        }
    }
}
