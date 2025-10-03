<?php

namespace App\Livewire\Finanzas;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pedidos\Pedido;
use App\Models\Pago;
use App\Models\InventarioRuta\GastoRuta;
use App\Models\User; // lista para el filtro de conductor
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Masmerise\Toaster\PendingToast;

class Creditos extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $filtroAplicado = false;

    public $pedidoSeleccionado;
    public $montoPago = '';
    public $showPagoModal = false;

    public $estadoFiltro = 'todos';
    public $tipoPagoFiltro = 'todos';
    public $fechaInicio;
    public $fechaFin;
    public $filtroCliente = '';

    // NUEVO: filtro por conductor (usa user_id en la tabla pedidos)
    public $conductorFiltro = 'todos';
    public $conductores = [];

    public $gastosAgrupados = [];
    public $totalOrdenes = 0;
    public $totalContado = 0;
    public $totalFacturado = 0;
    public $totalValorCredito = 0;
    public $totalClientesConDeuda = 0;
    public $totalGastos = 0;
    public $gastosAdministrativos = 0;
    public $totalesPorTipoGasto = [];

    public $mostrarDetalle = false;
    public $pedidoDetalleSeleccionado;
    public $pedidosFiltrados = [];

    protected $listeners = ['abrirModalPago'];

    public function mount()
    {
        $this->fechaInicio = null;
        $this->fechaFin = null;
        $this->filtroAplicado = false;

        // Carga de conductores (si manejas roles, filtra por rol aquí)
        $this->conductores = User::select('id', 'name')->orderBy('name')->get();
    }

    public function updated($property)
    {
        if (in_array($property, [
            'estadoFiltro',
            'fechaInicio',
            'fechaFin',
            'tipoPagoFiltro',
            'filtroCliente',
            'conductorFiltro',
        ])) {
            $this->resetPage(); // sólo resetea la página; la carga se hace al dar "Buscar"
        }
    }

    public function limpiarFiltros()
    {
        $this->reset([
            'estadoFiltro',
            'tipoPagoFiltro',
            'fechaInicio',
            'fechaFin',
            'filtroCliente',
            'filtroAplicado',
            'pedidosFiltrados',
            'conductorFiltro',
        ]);

        $this->estadoFiltro = 'todos';
        $this->tipoPagoFiltro = 'todos';
        $this->conductorFiltro = 'todos';
        $this->resetPage();
    }

    public function cargarPedidosCredito()
    {
        $this->filtroAplicado = true;

        $query = Pedido::with([
            'socioNegocio',
            'pagos',
            'detalles.producto',
            'detalles.precioLista',
            'conductor', // relación que usa user_id
            'ruta',
        ])->where('estado', '!=', 'cancelado');

        // Tipo de pago
        if ($this->tipoPagoFiltro !== 'todos') {
            $query->where('tipo_pago', $this->tipoPagoFiltro);
        } else {
            $query->whereIn('tipo_pago', ['credito', 'contado', 'transferencia']);
        }

        // Rango de fechas
        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('fecha', [$this->fechaInicio, $this->fechaFin]);
        }

        // Filtro por conductor (usa user_id)
        if ($this->conductorFiltro !== 'todos') {
            $query->where('user_id', (int) $this->conductorFiltro);
        }

        $todosLosPedidos = $query->orderByDesc('fecha')->get();

        // Filtro por cliente (texto)
        if (!empty($this->filtroCliente)) {
            $todosLosPedidos = $todosLosPedidos->filter(function ($pedido) {
                return str_contains(
                    strtolower($pedido->socioNegocio->razon_social ?? ''),
                    strtolower($this->filtroCliente)
                );
            });
        }

        $this->totalOrdenes = $todosLosPedidos->count();
        $this->totalContado = $todosLosPedidos->where('tipo_pago', 'contado')->count();
        $this->totalValorCredito = $todosLosPedidos
            ->where('tipo_pago', 'credito')
            ->sum(fn($pedido) => $pedido->detalles->sum(fn($d) => $d->cantidad * ($d->producto->precio ?? 0)));

        // Gastos
        $this->gastosAgrupados = collect();
        $this->totalGastos = 0;
        $this->gastosAdministrativos = 0;

        if ($this->fechaInicio && $this->fechaFin) {
            $gastosFiltrados = GastoRuta::whereBetween('created_at', [$this->fechaInicio, $this->fechaFin])->get();

            $this->gastosAgrupados = $gastosFiltrados
                ->whereNotNull('ruta_id')
                ->groupBy(fn($gasto) => $gasto->ruta_id . '_' . Carbon::parse($gasto->created_at)->format('Y-m-d'));

            $this->totalGastos = $gastosFiltrados->sum('monto');
            $this->gastosAdministrativos = $gastosFiltrados->whereNull('ruta_id')->sum('monto');
        }

        // Filtrar por estado final (pendiente, parcial, pagado)
        $this->totalFacturado = 0;

        $this->pedidosFiltrados = $todosLosPedidos->filter(function ($pedido) {
            $fechaPedido = Carbon::parse($pedido->fecha)->format('Y-m-d');
            $rutaId = $pedido->ruta_id;

            $totalPedido = $pedido->detalles->sum(fn($d) => $d->cantidad * ($d->producto->precio ?? 0));
            $totalPagado = $pedido->pagos->sum('monto');

            $claveGasto = $rutaId . '_' . $fechaPedido;
            $gastos = $this->gastosAgrupados[$claveGasto] ?? collect();
            $totalGastos = $gastos->sum('monto');

            $estado = match (true) {
                $pedido->tipo_pago === 'contado' => 'pagado',
                $totalPagado == 0                => 'pendiente',
                $totalPagado < $totalPedido      => 'parcial',
                default                          => 'pagado',
            };

            if ($estado === 'pagado') {
                $this->totalFacturado += $totalPedido;
            }

            return match ($this->estadoFiltro) {
                'pendiente' => $estado === 'pendiente',
                'parcial'   => $estado === 'parcial',
                'pagado'    => $estado === 'pagado',
                default     => true,
            };
        })->values();
    }

    public function calcularClientesConDeuda()
    {
        $this->totalClientesConDeuda = Pedido::with('pagos')
            ->where('tipo_pago', 'credito')
            ->get()
            ->filter(fn($p) => $p->pagos->sum('monto') < $p->detalles->sum(fn($d) => $d->cantidad * ($d->producto->precio ?? 0)))
            ->sum(fn($p) => $p->detalles->sum(fn($d) => $d->cantidad * ($d->producto->precio ?? 0)) - $p->pagos->sum('monto'));
    }

    public function abrirModalPago($pedidoId)
    {
        $this->pedidoSeleccionado = Pedido::with(['pagos', 'socioNegocio', 'conductor'])->findOrFail($pedidoId);
        $this->montoPago = '';
        $this->showPagoModal = true;
    }

    public function registrarPago()
    {
        $this->validate([
            'montoPago' => 'required|numeric|min:1',
        ]);

        $totalPedido = $this->pedidoSeleccionado->detalles->sum(fn($d) => $d->cantidad * ($d->producto->precio ?? 0));
        $totalPagado = $this->pedidoSeleccionado->pagos->sum('monto');
        $restante = $totalPedido - $totalPagado;

        if ($this->montoPago > $restante) {
            PendingToast::create()
                ->error()
                ->message('El monto ingresado excede el saldo pendiente del pedido.')
                ->duration(6000);
            return;
        }

        DB::beginTransaction();

        try {
            Pago::create([
                'pedido_id'        => $this->pedidoSeleccionado->id,
                'socio_negocio_id' => $this->pedidoSeleccionado->socio_negocio_id,
                'monto'            => $this->montoPago,
                'fecha'            => now(),
                'metodo_pago'      => 'efectivo',
                'observaciones'    => 'Pago registrado desde módulo de finanzas',
            ]);

            DB::commit();
            $this->showPagoModal = false;

            PendingToast::create()
                ->success()
                ->message('Pago registrado correctamente.')
                ->duration(5000);

            $this->cargarPedidosCredito();
        } catch (\Exception $e) {
            DB::rollBack();

            PendingToast::create()
                ->error()
                ->message('Error al registrar el pago: ' . $e->getMessage())
                ->duration(8000);
        }
    }

    public function verDetalle($id)
    {
        $this->pedidoDetalleSeleccionado = Pedido::with([
            'detalles.producto',
            'socioNegocio',
            'ruta',
            'conductor'
        ])->findOrFail($id);

        $this->mostrarDetalle = true;
    }

    public function render()
    {
        // Asegura datos al paginar
        if ($this->filtroAplicado && empty($this->pedidosFiltrados)) {
            $this->cargarPedidosCredito();
        }

        if (!$this->filtroAplicado) {
            $pedidosCredito = new LengthAwarePaginator([], 0, 10);
            return view('livewire.finanzas.creditos', [
                'pedidosCredito' => $pedidosCredito,
                'conductores'    => $this->conductores,
            ]);
        }

        $items = collect($this->pedidosFiltrados ?? []);
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $totalPages = (int) ceil($items->count() / $perPage);

        if ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = 1;
        }

        $currentItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $pedidosCredito = new LengthAwarePaginator(
            $currentItems,
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.finanzas.creditos', [
            'pedidosCredito' => $pedidosCredito,
            'conductores'    => $this->conductores,
        ]);
    }
}
