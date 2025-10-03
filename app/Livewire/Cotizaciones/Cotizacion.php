<?php

namespace App\Livewire\Cotizaciones;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

use App\Models\bodegas;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Productos\Producto;
use App\Models\Productos\PrecioProducto;
use App\Models\cotizaciones\cotizacione;
use App\Models\Pedidos\Pedido;
use App\Models\Pedidos\PedidoDetalle;
use Barryvdh\DomPDF\Facade\Pdf;
use Masmerise\Toaster\PendingToast;

class Cotizacion extends Component
{
    public ?cotizacione $cotizacion = null;

    public string $tab = 'lineas';
    public $socio_negocio_id;
    public $fecha, $vencimiento, $lista_precio, $terminos_pago, $notas;
    public $estado = 'borrador';
    public $lineas = [];

    protected $rules = [
        'socio_negocio_id'         => 'required|exists:socio_negocios,id',
        'fecha'                    => 'required|date',
        'vencimiento'              => 'nullable|date|after_or_equal:fecha',
        'lineas'                   => 'array|min:1',
        'lineas.*.producto_id'     => 'required|exists:productos,id',
        'lineas.*.cantidad'        => 'required|numeric|min:0.001',
        'lineas.*.precio_unitario' => 'required|numeric|min:0',
    ];

    #[On('abrir-cotizacion')]
    public function abrirDesdeLista(int $id): void
    {
        $this->cargarCotizacion($id);

        // Cierra/limpia el modal del hijo si quedó abierto
        $this->dispatch('cerrar-modal-enviar')
             ->to(\App\Livewire\Cotizaciones\EnviarCotizacionCorreo::class);
    }

    public function mount(?int $id = null): void
    {
        $this->fecha = now()->toDateString();
        $id ? $this->cargarCotizacion($id) : $this->addLinea();
    }

    public function render()
    {
        $bodegas   = bodegas::orderBy('nombre')->get();
        $clientes  = SocioNegocio::clientes()->orderBy('razon_social')->take(100)->get();
        $productos = Producto::where('activo',1)->orderBy('nombre')->take(200)->get();

        return view('livewire.cotizaciones.cotizacion', compact('clientes','productos','bodegas'));
    }

    private function setFromModel(cotizacione $c): void
    {
        $this->cotizacion = $c;

        $this->fill($c->only([
            'socio_negocio_id','fecha','vencimiento','lista_precio','terminos_pago','notas','estado'
        ]));

        $this->lineas = $c->detalles->map(fn($d) => [
            'id'              => $d->id,
            'producto_id'     => $d->producto_id,
            'bodega_id'       => $d->bodega_id,
            'cantidad'        => (float)$d->cantidad,
            'precio_unitario' => (float)$d->precio_unitario,
            'precio_lista_id' => $d->precio_lista_id,
            'descuento_pct'   => (float)$d->descuento_pct,
            'impuesto_pct'    => (float)$d->impuesto_pct,
            'importe'         => (float)$d->importe,
        ])->toArray();

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function cargarCotizacion(int $id): void
    {
        $c = cotizacione::with('detalles')->findOrFail($id);
        $this->setFromModel($c);
    }

    private function toastError(Throwable $e, string $contexto): void
    {
        report($e);
        $msg = config('app.debug')
            ? sprintf("%s @ %s:%d", $e->getMessage(), basename($e->getFile()), $e->getLine())
            : 'Ocurrió un error y fue registrado en el log.';
        PendingToast::create()->error()->message($msg)->duration(12000);
    }

    protected function ref(): string
    {
        $id = $this->cotizacion?->id ?? 0;
        return 'S'.str_pad((string)$id, 5, '0', STR_PAD_LEFT);
    }

    protected function totalCalculado(): float
    {
        $sub = collect($this->lineas)->sum('importe');
        $imp = collect($this->lineas)->sum(function($l){
            $base = ($l['cantidad']*$l['precio_unitario'])*(1 - ($l['descuento_pct'] ?? 0)/100);
            return $base * (($l['impuesto_pct'] ?? 0)/100);
        });
        return round($sub + $imp, 2);
    }

    protected function generarPdfCotizacion(): array
    {
        $ref = $this->ref();
        $fileName = "Cotización - {$ref}.pdf";
        $path = storage_path('app/tmp/'.Str::uuid().'.pdf');

        $this->persistir();

        $pdf = Pdf::loadView('pdf.cotizacion', [
            'cotizacion' => $this->cotizacion->fresh('detalles'),
            'lineas'     => $this->lineas,
            'ref'        => $ref,
            'total'      => $this->totalCalculado(),
        ])->setPaper('letter');

        @mkdir(dirname($path), 0775, true);
        $pdf->save($path);

        return [$path, $fileName];
    }

    /* ---------- líneas ---------- */
    public function addLinea(): void
    {
        $this->lineas[] = [
            'producto_id'     => null,
            'bodega_id'       => null,
            'cantidad'        => 1,
            'precio_unitario' => 0,
            'precio_lista_id' => null,
            'descuento_pct'   => 0,
            'impuesto_pct'    => 0,
            'importe'         => 0,
        ];
    }
    public function removeLinea($i){ array_splice($this->lineas,$i,1); $this->recalcular(); }
    public function setProducto($i,$id){ $this->lineas[$i]['producto_id']=$id; $this->sugerirPrecio($i); $this->recalcularLinea($i); }
    public function setCantidad($i,$v){ $this->lineas[$i]['cantidad']=(float)$v; $this->recalcularLinea($i); }
    public function setPrecio($i,$v){ $this->lineas[$i]['precio_unitario']=(float)$v; $this->recalcularLinea($i); }
    public function setDescuento($i,$v){ $this->lineas[$i]['descuento_pct']=(float)$v; $this->recalcularLinea($i); }
    public function setImpuesto($i,$v){ $this->lineas[$i]['impuesto_pct']=(float)$v; $this->recalcularLinea($i); }

    protected function sugerirPrecio($i): void
    {
        $productoId = $this->lineas[$i]['producto_id'] ?? null;
        if (!$productoId) return;

        if ($this->lista_precio) {
            $pl = PrecioProducto::where('producto_id',$productoId)->where('nombre',$this->lista_precio)->first();
            if ($pl) {
                $this->lineas[$i]['precio_unitario'] = (float)$pl->valor;
                $this->lineas[$i]['precio_lista_id'] = $pl->id;
                return;
            }
        }
        if ($p = Producto::find($productoId)) {
            $this->lineas[$i]['precio_unitario'] = (float)($p->precio ?? 0);
            $this->lineas[$i]['precio_lista_id'] = null;
        }
    }

    protected function recalcularLinea($i): void
    {
        $l = $this->lineas[$i];
        $base = ($l['cantidad'] * $l['precio_unitario']) * (1 - $l['descuento_pct']/100);
        $this->lineas[$i]['importe'] = round($base, 2);
        $this->recalcular();
    }

    protected function recalcular(): void
    {
        if (!$this->cotizacion) return;
        $sub = collect($this->lineas)->sum('importe');
        $imp = collect($this->lineas)->sum(function($l){
            $base = ($l['cantidad']*$l['precio_unitario'])*(1 - $l['descuento_pct']/100);
            return $base * ($l['impuesto_pct']/100);
        });
        $this->cotizacion->subtotal  = $sub;
        $this->cotizacion->impuestos = $imp;
        $this->cotizacion->total     = $sub + $imp;
    }

    protected function persistir(): void
    {
        DB::transaction(function () {
            $data = [
                'socio_negocio_id' => $this->socio_negocio_id,
                'fecha'            => $this->fecha,
                'vencimiento'      => $this->vencimiento,
                'lista_precio'     => $this->lista_precio,
                'terminos_pago'    => $this->terminos_pago,
                'notas'            => $this->notas,
                'estado'           => $this->estado,
            ];

            if (!$this->cotizacion) {
                $this->cotizacion = cotizacione::create($data);
            } else {
                $this->cotizacion->update($data);
                $this->cotizacion->detalles()->delete();
            }

            foreach ($this->lineas as $l) {
                $detalle = $this->cotizacion->detalles()->create([
                    'producto_id'     => $l['producto_id'],
                    'bodega_id'       => $l['bodega_id'] ?? null,
                    'cantidad'        => (float)$l['cantidad'],
                    'precio_unitario' => (float)$l['precio_unitario'],
                    'precio_lista_id' => $l['precio_lista_id'] ?? null,
                    'descuento_pct'   => $l['descuento_pct'] ?? 0,
                    'impuesto_pct'    => $l['impuesto_pct'] ?? 0,
                    'importe'         => $l['importe'] ?? 0,
                ]);
                $detalle->recalcularImporte();
            }

            $this->cotizacion->load('detalles');
            $this->cotizacion->recalcularTotales();
        });
    }

    public function guardar()
    {
        try {
            $this->validate();
            if (empty($this->lineas)) {
                PendingToast::create()->error()->message('Ingresa al menos una línea antes de guardar.')->duration(5000);
                return;
            }
            $this->persistir();
            PendingToast::create()->success()->message('Cotización guardada correctamente.')->duration(4500);
            session()->flash('ok','Cotización guardada.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            PendingToast::create()->error()->message('Revisa los campos obligatorios de la cotización.')->duration(5000);
            throw $e;
        } catch (Throwable $e) {
            $this->toastError($e, 'No se pudo guardar la cotización');
        }
    }

    /** Enviar: abre modal del hijo */
    public function enviar()
    {
        try {
            $this->validate();
            if (empty($this->lineas)) {
                PendingToast::create()->error()->message('Ingresa al menos una línea antes de enviar.')->duration(5000);
                return;
            }

            $this->persistir();

            // Abrir modal en el hijo con la cotización actual
            $this->dispatch('abrir-modal-enviar', cotizacionId: $this->cotizacion->id)
                 ->to(\App\Livewire\Cotizaciones\EnviarCotizacionCorreo::class);

        } catch (Throwable $e) {
            $this->toastError($e, 'Error al preparar envío');
        }
    }

    public function aprobarYGenerarPedido()
    {
        try {
            $this->validate();
            if (empty($this->lineas)) {
                PendingToast::create()->error()->message('La cotización no tiene líneas.')->duration(5000);
                return;
            }
            $this->persistir();

            if ($this->cotizacion->pedido_id) {
                PendingToast::create()->warning()->message('Esta cotización ya fue convertida (#'.$this->cotizacion->pedido_id.').')->duration(6000);
                return;
            }

            $pedidoId = DB::transaction(function () {
                $pedido = Pedido::create([
                    'ruta_id'          => null,
                    'socio_negocio_id' => $this->cotizacion->socio_negocio_id,
                    'user_id'          => Auth::id(),
                    'fecha'            => now()->toDateString(),
                    'tipo_pago'        => 'contado',
                    'valor_credito'    => 0,
                    'estado'           => 'pendiente',
                ]);

                foreach ($this->cotizacion->detalles as $d) {
                    PedidoDetalle::create([
                        'pedido_id'       => $pedido->getKey(),
                        'producto_id'     => $d->producto_id,
                        'bodega_id'       => $d->bodega_id,
                        'cantidad'        => (float)$d->cantidad,
                        'precio_unitario' => (float)$d->precio_unitario,
                        'precio_lista_id' => $d->precio_lista_id ?: null,
                        'estado'          => 'abierto',
                    ]);
                }

                $this->cotizacion->update([
                    'estado'       => 'convertida',
                    'pedido_id'    => $pedido->getKey(),
                    'aprobada_at'  => now(),
                    'aprobada_por' => Auth::id(),
                ]);

                return $pedido->getKey();
            });

            PendingToast::create()->success()->message("Pedido #{$pedidoId} creado.")->duration(6000);
            session()->flash('ok','Orden de venta generada. Pedido #'.$pedidoId);
        } catch (Throwable $e) {
            $this->toastError($e, 'No se pudo convertir la cotización a pedido');
        }
    }

    public function cancelar()
    {
        try {
            if(!$this->cotizacion){
                PendingToast::create()->warning()->message('No hay cotización para cancelar.')->duration(4000);
                return;
            }
            $this->cotizacion->update(['estado' => 'cancelada']);
            PendingToast::create()->info()->message('Cotización cancelada.')->duration(4000);
            session()->flash('ok','Cotización cancelada.');
        } catch (Throwable $e) {
            $this->toastError($e, 'No se pudo cancelar la cotización');
        }
    }
}
